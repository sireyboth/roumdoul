<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use Filament\Widgets\Widget;

/**
 * Shop visitors → orders → paid; invitations sold in the period → filled in → sent to guests; and their guest letters → opened → replied →
 * attending. A big drop between two steps shows where customers or guests get stuck.
 */
class InvitationFunnel extends Widget
{
    use UsesDashboardPeriod;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 3];

    protected string $view = 'filament.widgets.invitation-funnel';

    protected function getViewData(): array
    {
        $f = $this->metrics()->funnel();
        $s = $this->metrics()->summary();

        return [
            'groups' => [
                'Shop' => [
                    ['Visitors', $s['visitors'], 'Opened a product page or demo'],
                    ['Orders placed', $s['placed_orders'], 'Checked out'],
                    ['Paid', $s['paid_orders'], 'Payment confirmed'],
                ],
                'Invitations sold' => [
                    ['Sold', $f['sold'], 'Bought in this period'],
                    ['Filled in', $f['filled'], 'Customer added their details'],
                    ['Sent to guests', $f['with_guests'], 'At least one guest link made'],
                ],
                'Guest letters' => [
                    ['Letters sent', $f['letters'], 'Guest links made'],
                    ['Opened', $f['opened'], 'Guest opened the link'],
                    ['Replied', $f['replied'], 'Answered the RSVP'],
                    ['Attending', $f['attending'], 'Said yes'],
                ],
            ],
        ];
    }
}
