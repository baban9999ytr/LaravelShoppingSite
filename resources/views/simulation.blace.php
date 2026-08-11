<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sipariş İade Simülatörü</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container" style="max-width: 700px;">
        <div class="card shadow p-4">
            <h2 class="mb-4">📦 Sipariş İade Zaman Simülatörü</h2>

            @if(session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <ul class="list-group mb-4">
                <li class="list-group-item"><strong>Sipariş ID:</strong> {{ $order->id }}</li>
                <li class="list-group-item"><strong>Şu Anki Sistem Saati:</strong> {{ $now->format('d.m.Y H:i') }}</li>
                <li class="list-group-item"><strong>Tahmini Teslimat (estimated_arrival_at):</strong> {{ $order->estimated_arrival_at->format('d.m.Y H:i') }}</li>
                <li class="list-group-item"><strong>İade Son Tarihi (Teslimat + 15 Gün):</strong> {{ $refundDeadline->format('d.m.Y H:i') }}</li>
                <li class="list-group-item">
                    <strong>Durum:</strong> 
                    @if(!$isDelivered)
                        <span class="badge bg-warning text-dark">Kargoda (Teslim Edilmedi)</span>
                    @elseif($isRefundable)
                        <span class="badge bg-success">İade Penceresi AÇIK (15 Gün İçinde)</span>
                    @else
                        <span class="badge bg-danger">İade Penceresi KAPALI (15 Gün Geçti)</span>
                    @endif
                </li>
            </ul>

            <!-- Simulation Controls -->
            <form action="{{ route('orders.simulate.time', $order->id) }}" method="POST" class="card p-3 bg-white mb-3">
                @csrf
                <h5 class="mb-3">Zamanı Simüle Et (Hızlı İleri Al)</h5>
                <input type="hidden" name="action_type" value="add_days">
                <div class="input-group mb-2">
                    <input type="number" name="days" class="form-control" placeholder="Örn: 5 (Gün önce teslim edildi say)" required>
                    <button class="btn btn-dark" type="submit">Zamanı İleri Sar</button>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="action_type" value="set_delivered" class="btn btn-outline-secondary btn-sm w-100" onclick="this.form.action_type.value='set_delivered'">Tam Şimdi Teslim Edildi Yap</button>
                    <button type="submit" name="action_type" value="reset" class="btn btn-outline-danger btn-sm w-100" onclick="this.form.action_type.value='reset'">Sıfırla</button>
                </div>
            </form>

            <!-- Test Refund Button -->
            @if($isRefundable)
                <form action="{{ route('orders.simulate.refund', $order->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger w-100 py-2">İade Et ve Stokları Geri Yükle</button>
                </form>
            @else
                <button class="btn btn-secondary w-100 py-2" disabled>İade Süresi Uygun Değil (Test için zamanı ileri sarın)</button>
            @endif
        </div>
    </div>
</body>
</html>