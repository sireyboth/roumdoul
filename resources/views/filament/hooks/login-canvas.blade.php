<canvas id="rd-aurora" class="rd-aurora"></canvas>

<div class="rd-watermark" aria-hidden="true">រំដួល</div>

<div class="rd-particles" aria-hidden="true">
    @for ($i = 0; $i < 24; $i++)
        @php
            $left = rand(0, 100);
            $size = rand(2, 5);
            $duration = rand(14, 26);
            $delay = rand(0, 20);
            $drift = rand(-40, 40);
        @endphp
        <span
            class="rd-particle"
            style="
                left: {{ $left }}%;
                width: {{ $size }}px;
                height: {{ $size }}px;
                animation-duration: {{ $duration }}s, {{ $duration * 0.6 }}s;
                animation-delay: -{{ $delay }}s, -{{ $delay }}s;
                --drift: {{ $drift }}px;
            "
        ></span>
    @endfor
</div>

<script>
(function () {
    var canvas = document.getElementById('rd-aurora');
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var width, height, dpr;
    var mouse = { x: 0.5, y: 0.35, targetX: 0.5, targetY: 0.35 };

    var blobs = [
        { color: '176, 35, 97',  baseX: 0.20, baseY: 0.25, r: 0.55, sx: 0.11, sy: 0.09, px: 0.4, py: 1.7 },
        { color: '199, 154, 68', baseX: 0.82, baseY: 0.20, r: 0.45, sx: 0.08, sy: 0.12, px: 1.9, py: 0.6 },
        { color: '224, 112, 159',baseX: 0.75, baseY: 0.85, r: 0.50, sx: 0.10, sy: 0.07, px: 3.1, py: 2.4 },
        { color: '53, 10, 30',   baseX: 0.30, baseY: 0.80, r: 0.60, sx: 0.07, sy: 0.10, px: 2.2, py: 0.9 },
    ];

    function resize() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        width = canvas.clientWidth;
        height = canvas.clientHeight;
        canvas.width = width * dpr;
        canvas.height = height * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function draw(t) {
        ctx.clearRect(0, 0, width, height);
        ctx.fillStyle = '#170b12';
        ctx.fillRect(0, 0, width, height);

        ctx.globalCompositeOperation = 'screen';

        blobs.forEach(function (b) {
            var x = (b.baseX + Math.sin(t * 0.00008 + b.px) * b.sx) * width;
            var y = (b.baseY + Math.cos(t * 0.00008 + b.py) * b.sy) * height;
            var r = b.r * Math.max(width, height);

            var grad = ctx.createRadialGradient(x, y, 0, x, y, r);
            grad.addColorStop(0, 'rgba(' + b.color + ', 0.55)');
            grad.addColorStop(0.5, 'rgba(' + b.color + ', 0.18)');
            grad.addColorStop(1, 'rgba(' + b.color + ', 0)');

            ctx.fillStyle = grad;
            ctx.beginPath();
            ctx.arc(x, y, r, 0, Math.PI * 2);
            ctx.fill();
        });

        // Cursor-reactive spotlight
        mouse.x += (mouse.targetX - mouse.x) * 0.06;
        mouse.y += (mouse.targetY - mouse.y) * 0.06;

        var sx = mouse.x * width;
        var sy = mouse.y * height;
        var sr = Math.max(width, height) * 0.35;
        var spot = ctx.createRadialGradient(sx, sy, 0, sx, sy, sr);
        spot.addColorStop(0, 'rgba(217, 182, 106, 0.16)');
        spot.addColorStop(1, 'rgba(217, 182, 106, 0)');
        ctx.fillStyle = spot;
        ctx.beginPath();
        ctx.arc(sx, sy, sr, 0, Math.PI * 2);
        ctx.fill();

        ctx.globalCompositeOperation = 'source-over';

        if (!reduceMotion) {
            requestAnimationFrame(draw);
        }
    }

    window.addEventListener('resize', resize, { passive: true });
    window.addEventListener('pointermove', function (e) {
        mouse.targetX = e.clientX / window.innerWidth;
        mouse.targetY = e.clientY / window.innerHeight;
    }, { passive: true });

    resize();
    draw(0);
    if (reduceMotion) {
        // Render a couple of extra frames so the gradient settles, then stop.
        setTimeout(function () { draw(400); }, 50);
    }

    // Subtle 3D tilt on the login card, following the cursor.
    var card = document.querySelector('.fi-simple-page-content .fi-section');
    if (card && !reduceMotion && window.matchMedia('(pointer: fine)').matches) {
        card.style.transformStyle = 'preserve-3d';
        card.style.willChange = 'transform';

        document.addEventListener('pointermove', function (e) {
            var rect = card.getBoundingClientRect();
            var cx = rect.left + rect.width / 2;
            var cy = rect.top + rect.height / 2;
            var dx = (e.clientX - cx) / (rect.width / 2);
            var dy = (e.clientY - cy) / (rect.height / 2);
            var max = 5;

            dx = Math.max(-1, Math.min(1, dx));
            dy = Math.max(-1, Math.min(1, dy));

            card.style.transform = 'perspective(1200px) rotateY(' + (dx * max) + 'deg) rotateX(' + (-dy * max) + 'deg)';
        }, { passive: true });

        card.addEventListener('pointerleave', function () {
            card.style.transform = 'perspective(1200px) rotateY(0deg) rotateX(0deg)';
        });
    }
})();
</script>
