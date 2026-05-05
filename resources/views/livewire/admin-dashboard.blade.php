<?php

use App\Models\Queue;
use App\Models\Poli;
use Livewire\Volt\Component;

new class extends Component {
    public function getStatsProperty()
    {
        $today = today();
        return [
            'total' => Queue::whereDate('created_at', $today)->count(),
            'finished' => Queue::whereDate('created_at', $today)->where('status', 'finished')->count(),
            'waiting' => Queue::whereDate('created_at', $today)->whereIn('status', ['waiting', 'calling'])->count(),
            'avg_wait' => $this->calculateAvgWait()
        ];
    }

    public function getRecentQueuesProperty()
    {
        return Queue::with('poli')
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }

    private function calculateAvgWait()
    {
        $queues = Queue::whereDate('created_at', today())
            ->whereNotNull('called_at')
            ->get();

        if ($queues->isEmpty()) return '0m';

        $totalMinutes = $queues->reduce(function ($carry, $item) {
            return $carry + $item->called_at->diffInMinutes($item->created_at);
        }, 0);

        $avg = round($totalMinutes / $queues->count());
        return $avg . 'm';
    }
}; ?>

<div wire:poll.5s>
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-label">Total Antrian Hari Ini</div>
            <div class="stat-value">{{ $this->stats['total'] }}</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #e8f5e9;">
                <i class="fas fa-check-circle" style="color: #2e7d32;"></i>
            </div>
            <div class="stat-label">Sudah Dilayani</div>
            <div class="stat-value">{{ $this->stats['finished'] }}</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #fff3e0;">
                <i class="fas fa-clock" style="color: #ef6c00;"></i>
            </div>
            <div class="stat-label">Dalam Antrian</div>
            <div class="stat-value">{{ $this->stats['waiting'] }}</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #fce4ec;">
                <i class="fas fa-user-clock" style="color: #c2185b;"></i>
            </div>
            <div class="stat-label">Rata-rata Tunggu</div>
            <div class="stat-value">{{ $this->stats['avg_wait'] }}</div>
        </div>
    </div>

    <!-- Recent Activity Table -->
    <div class="content-card">
        <div class="card-header">
            <h3 style="color: var(--accent-color); font-weight: 700;">Aktivitas Antrian Terbaru</h3>
            <div style="font-size: 0.8rem; color: var(--text-muted);">Auto-refresh aktif</div>
        </div>
        
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
                <thead>
                    <tr style="text-align: left; color: var(--text-muted); font-size: 0.9rem;">
                        <th style="padding: 1rem;">NO. TIKET</th>
                        <th style="padding: 1rem;">POLI</th>
                        <th style="padding: 1rem;">WAKTU DAFTAR</th>
                        <th style="padding: 1rem;">STATUS</th>
                        <th style="padding: 1rem;">WAKTU PANGGIL</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->recentQueues as $q)
                    <tr style="background-color: var(--bg-color); border-radius: 15px;">
                        <td style="padding: 1.2rem; font-weight: 700; border-radius: 15px 0 0 15px;">{{ $q->ticket_number }}</td>
                        <td style="padding: 1.2rem;">
                            <span style="background: rgba(145, 208, 108, 0.2); padding: 4px 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 600;">
                                {{ $q->poli->name }}
                            </span>
                        </td>
                        <td style="padding: 1.2rem; color: var(--text-muted);">{{ $q->created_at->format('H:i:s') }}</td>
                        <td style="padding: 1.2rem;">
                            @php
                                $statusColors = [
                                    'waiting' => '#888',
                                    'calling' => '#ef6c00',
                                    'serving' => '#346739',
                                    'finished' => '#2e7d32',
                                    'skipped' => '#f44336'
                                ];
                            @endphp
                            <span style="color: {{ $statusColors[$q->status] ?? '#888' }}; font-weight: 600;">
                                <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 5px; vertical-align: middle;"></i>
                                {{ ucfirst($q->status) }}
                            </span>
                        </td>
                        <td style="padding: 1.2rem; border-radius: 0 15px 15px 0; color: var(--text-muted);">
                            {{ $q->called_at ? $q->called_at->format('H:i:s') : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 3rem; color: #ccc;">Belum ada data hari ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
