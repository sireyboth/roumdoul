<?php

namespace App\Livewire\Pages\Dashboard;

use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Customer dashboard: summary stats plus two independently searchable, filterable, sortable
 * and paginated lists (invitations and orders). Every list setting lives in the query string,
 * so a refresh or a shared link reopens exactly the same view.
 */
#[Layout('components.layouts.app')]
class DashboardPage extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [6, 12, 24, 48];

    public const INVITATION_STATUSES = ['active', 'unpaid', 'expired'];

    public const INVITATION_SORTS = ['newest', 'oldest', 'expiring', 'guests'];

    public const ORDER_STATUSES = ['pending_payment', 'paid', 'fulfilled', 'cancelled'];

    /** Columns the orders table can be sorted by (whitelisted — this goes into orderBy). */
    public const ORDER_SORTS = ['order_number', 'created_at', 'total', 'status'];

    #[Url]
    public string $tab = 'invitations';

    #[Url(as: 'iq', except: '')]
    public string $invitationSearch = '';

    #[Url(as: 'istatus', except: '')]
    public string $invitationStatus = '';

    #[Url(as: 'isort', except: 'newest')]
    public string $invitationSort = 'newest';

    #[Url(as: 'iper', except: 6)]
    public int $invitationPerPage = 6;

    #[Url(as: 'oq', except: '')]
    public string $orderSearch = '';

    #[Url(as: 'ostatus', except: '')]
    public string $orderStatus = '';

    #[Url(as: 'osort', except: 'created_at')]
    public string $orderSort = 'created_at';

    #[Url(as: 'odir', except: 'desc')]
    public string $orderDirection = 'desc';

    #[Url(as: 'oper', except: 12)]
    public int $orderPerPage = 12;

    public function mount(): void
    {
        $this->sanitize();
    }

    /** Any filter change sends that list back to page 1, so you never land on an empty page. */
    public function updated(string $property): void
    {
        $this->sanitize();

        if (str_starts_with($property, 'invitation')) {
            $this->resetPage('invitationsPage');
        } elseif (str_starts_with($property, 'order')) {
            $this->resetPage('ordersPage');
        }
    }

    public function sortOrdersBy(string $column): void
    {
        if (! in_array($column, self::ORDER_SORTS, true)) {
            return;
        }

        if ($this->orderSort === $column) {
            $this->orderDirection = $this->orderDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->orderSort = $column;
            // Dates and money read best biggest/newest first; text reads best A→Z.
            $this->orderDirection = in_array($column, ['created_at', 'total'], true) ? 'desc' : 'asc';
        }

        $this->resetPage('ordersPage');
    }

    public function clearInvitationFilters(): void
    {
        $this->reset('invitationSearch', 'invitationStatus', 'invitationSort');
        $this->resetPage('invitationsPage');
    }

    public function clearOrderFilters(): void
    {
        $this->reset('orderSearch', 'orderStatus', 'orderSort', 'orderDirection');
        $this->resetPage('ordersPage');
    }

    /** Query-string values are user input — snap anything unexpected back to a default. */
    protected function sanitize(): void
    {
        $this->tab = in_array($this->tab, ['invitations', 'orders'], true) ? $this->tab : 'invitations';
        $this->invitationSearch = mb_substr(trim($this->invitationSearch), 0, 100);
        $this->orderSearch = mb_substr(trim($this->orderSearch), 0, 100);

        if (! in_array($this->invitationStatus, self::INVITATION_STATUSES, true)) {
            $this->invitationStatus = '';
        }
        if (! in_array($this->invitationSort, self::INVITATION_SORTS, true)) {
            $this->invitationSort = 'newest';
        }
        if (! in_array($this->orderStatus, self::ORDER_STATUSES, true)) {
            $this->orderStatus = '';
        }
        if (! in_array($this->orderSort, self::ORDER_SORTS, true)) {
            $this->orderSort = 'created_at';
        }
        if (! in_array($this->orderDirection, ['asc', 'desc'], true)) {
            $this->orderDirection = 'desc';
        }
        if (! in_array($this->invitationPerPage, self::PER_PAGE_OPTIONS, true)) {
            $this->invitationPerPage = 6;
        }
        if (! in_array($this->orderPerPage, self::PER_PAGE_OPTIONS, true)) {
            $this->orderPerPage = 12;
        }
    }

    protected function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    protected function invitations(): LengthAwarePaginator
    {
        $query = $this->customer()->invitations()
            ->with(['template.service', 'order'])
            ->withCount([
                'recipients',
                'recipients as opened_count' => fn (Builder $q) => $q->whereNotNull('viewed_at'),
                'recipients as attending_count' => fn (Builder $q) => $q->where('rsvp_status', 'yes'),
            ]);

        if ($this->invitationSearch !== '') {
            $term = '%'.$this->escapeLike($this->invitationSearch).'%';
            // Match the template's name, or any guest on the invitation ("which one has Dara?").
            $query->where(fn (Builder $q) => $q
                ->whereHas('template', fn (Builder $t) => $t->where('name', 'like', $term))
                ->orWhereHas('recipients', fn (Builder $r) => $r->where('recipient_name', 'like', $term)));
        }

        match ($this->invitationStatus) {
            'active' => $query->whereHas('order', fn (Builder $o) => $o->whereIn('status', Order::PAID_STATUSES))
                ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())),
            'unpaid' => $query->whereDoesntHave('order', fn (Builder $o) => $o->whereIn('status', Order::PAID_STATUSES)),
            'expired' => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()),
            default => null,
        };

        match ($this->invitationSort) {
            'oldest' => $query->oldest()->oldest('id'),
            // Invitations without an end date go last.
            'expiring' => $query->orderByRaw('expires_at is null')->orderBy('expires_at')->latest('id'),
            'guests' => $query->orderByDesc('recipients_count')->latest('id'),
            default => $query->latest()->latest('id'),
        };

        return $query->paginate($this->invitationPerPage, pageName: 'invitationsPage');
    }

    protected function orders(): LengthAwarePaginator
    {
        // Customer::orders() carries a default latest(); drop it so our sort is the only one.
        $query = $this->customer()->orders()->reorder()->withCount('items');

        if ($this->orderSearch !== '') {
            $term = '%'.$this->escapeLike($this->orderSearch).'%';
            $query->where(fn (Builder $q) => $q
                ->where('order_number', 'like', $term)
                ->orWhereHas('items', fn (Builder $i) => $i->where('service_name_snapshot', 'like', $term)));
        }

        if ($this->orderStatus !== '') {
            $query->where('status', $this->orderStatus);
        }

        return $query
            ->orderBy($this->orderSort, $this->orderDirection)
            ->orderBy('id', $this->orderDirection)
            ->paginate($this->orderPerPage, pageName: 'ordersPage');
    }

    protected function stats(): array
    {
        $customer = $this->customer();
        $invitationIds = $customer->invitations()->select('id');

        return [
            'orders' => $customer->orders()->count(),
            'spent' => (float) $customer->orders()->reorder()->whereIn('status', Order::PAID_STATUSES)->sum('total'),
            'pending' => $customer->orders()->where('status', 'pending_payment')->count(),
            'invitations' => $customer->invitations()->count(),
            'active' => $customer->invitations()
                ->whereHas('order', fn (Builder $o) => $o->whereIn('status', Order::PAID_STATUSES))
                ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->count(),
            'guests' => InvitationRecipient::whereIn('invitation_id', $invitationIds)->count(),
            'opened' => InvitationRecipient::whereIn('invitation_id', $invitationIds)->whereNotNull('viewed_at')->count(),
        ];
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /** Best human title for an invitation card: the names on it, else the template's name. */
    public static function invitationTitle(Invitation $invitation): string
    {
        $v = $invitation->field_values ?? [];
        $pick = fn (string $k) => is_string($v[$k] ?? null) && trim($v[$k]) !== '' ? trim($v[$k]) : null;

        if ($pick('groom_name') && $pick('bride_name')) {
            return $pick('groom_name').' & '.$pick('bride_name');
        }

        return $pick('celebrant_name') ?? $pick('sender_name') ?? $invitation->template->name;
    }

    public function render()
    {
        return view('livewire.pages.dashboard.dashboard-page', [
            'customer' => $this->customer(),
            'stats' => $this->stats(),
            'invitations' => $this->invitations(),
            'orders' => $this->orders(),
            'orderStatusLabels' => [
                'pending_payment' => __('Pending payment'),
                'paid' => __('Paid'),
                'fulfilled' => __('Fulfilled'),
                'cancelled' => __('Cancelled'),
            ],
            'invitationStatusLabels' => [
                'active' => __('Active'),
                'unpaid' => __('Awaiting payment'),
                'expired' => __('Expired'),
            ],
            'invitationSortLabels' => [
                'newest' => __('Newest first'),
                'oldest' => __('Oldest first'),
                'expiring' => __('Expiring soon'),
                'guests' => __('Most guests'),
            ],
        ])->title(__('Dashboard | ROUMDOUL'));
    }
}
