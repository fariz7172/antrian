<?php

use App\Models\Poli;
use App\Models\Queue;
use Livewire\Volt\Component;

new class extends Component {
    public $selectedPoliId = null;
    
    public function mount()
    {
        if (auth()->user()->role === 'staff') {
            $this->selectedPoliId = auth()->user()->poli_id;
        } else {
            $firstPoli = Poli::active()->first();
            if ($firstPoli) {
                $this->selectedPoliId = $firstPoli->id;
            }
        }
    }

    public function getWaitingQueuesProperty()
    {
        if (!$this->selectedPoliId) return [];
        
        return Queue::where('poli_id', $this->selectedPoliId)
            ->where('status', 'waiting')
            ->whereDate('created_at', today())
            ->orderBy('number', 'asc')
            ->get();
    }

    public function getSkippedQueuesProperty()
    {
        if (!$this->selectedPoliId) return [];
        
        return Queue::where('poli_id', $this->selectedPoliId)
            ->where('status', 'skipped')
            ->whereDate('created_at', today())
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function getCurrentServingProperty()
    {
        if (!$this->selectedPoliId) return null;

        return Queue::where('poli_id', $this->selectedPoliId)
            ->whereIn('status', ['calling', 'serving'])
            ->whereDate('created_at', today())
            ->orderBy('called_at', 'desc')
            ->first();
    }

    public function callNext()
    {
        // Jika ada yang sedang dipanggil tapi belum diklik "Selesai", 
        // kembalikan ke daftar tunggu agar tidak hilang
        if ($this->currentServing) {
            $this->currentServing->update([
                'status' => 'waiting',
                'called_at' => null
            ]);
        }

        $next = Queue::where('poli_id', $this->selectedPoliId)
            ->where('status', 'waiting')
            ->whereDate('created_at', today())
            ->orderBy('number', 'asc')
            ->first();

        if ($next) {
            $next->update([
                'status' => 'calling',
                'called_at' => now()
            ]);
            $this->dispatch('announce-ticket', ticket: $next->ticket_number, poli: $next->poli->name);
        }
    }

    public function recall()
    {
        if ($this->currentServing) {
            $this->currentServing->update(['called_at' => now()]);
            $this->dispatch('announce-ticket', ticket: $this->currentServing->ticket_number, poli: $this->currentServing->poli->name);
        }
    }

    public function recallSkipped($id)
    {
        // Kembalikan nomor aktif saat ini ke daftar tunggu jika ada
        if ($this->currentServing) {
            $this->currentServing->update([
                'status' => 'waiting',
                'called_at' => null
            ]);
        }

        $queue = Queue::find($id);
        if ($queue) {
            $queue->update([
                'status' => 'calling',
                'called_at' => now()
            ]);
            $this->dispatch('announce-ticket', ticket: $queue->ticket_number, poli: $queue->poli->name);
        }
    }

    public function finish()
    {
        if ($this->currentServing) {
            $this->currentServing->update(['status' => 'finished', 'finished_at' => now()]);
        }
    }

    public function skip()
    {
        if ($this->currentServing) {
            $this->currentServing->update(['status' => 'skipped']);
        }
    }
}; ?>

<div class="staff-container" wire:poll.3s>
    <style>
        .staff-header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 24px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .poli-selector {
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            border: 2px solid var(--primary-color);
            background: var(--bg-color);
            font-weight: 600;
            color: var(--accent-color);
            outline: none;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }
        .serving-card {
            background: var(--accent-color);
            color: white;
            padding: 3rem;
            border-radius: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .current-number {
            font-size: 8rem;
            font-weight: 900;
            margin: 1rem 0;
            line-height: 1;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }
        .btn-staff {
            padding: 1rem;
            border-radius: 15px;
            border: none;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
        }
        .btn-call { background: var(--primary-color); color: var(--accent-color); grid-column: span 3; font-size: 1.2rem; }
        .btn-recall { background: rgba(255,255,255,0.2); color: white; }
        .btn-finish { background: #4caf50; color: white; }
        .btn-skip { background: #f44336; color: white; }
        
        .side-panel {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .waiting-list, .skipped-list {
            background: white;
            border-radius: 24px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        .waiting-item, .skipped-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }
        .btn-mini-call {
            background: var(--primary-color);
            color: var(--accent-color);
            border: none;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
        }
    </style>

    <div class="staff-header">
        <div>
            <h2 style="color: var(--accent-color); margin: 0;">Control Panel Petugas</h2>
            <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">
                Poli Aktif: <strong>{{ auth()->user()->poli?->name ?? 'Semua Poli' }}</strong>
            </p>
        </div>
        
        @if(auth()->user()->role === 'superadmin')
            <select wire:model.live="selectedPoliId" class="poli-selector">
                @foreach(App\Models\Poli::all() as $poli)
                    <option value="{{ $poli->id }}">{{ $poli->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <div class="dashboard-grid">
        <div class="main-panel">
            <div class="serving-card">
                <div style="text-transform: uppercase; letter-spacing: 2px; font-weight: 500; opacity: 0.8;">Sedang Dipanggil</div>
                @if($this->currentServing)
                    <div class="current-number">{{ $this->currentServing->ticket_number }}</div>
                    <div style="font-size: 1.2rem; opacity: 0.9;">Status: {{ ucfirst($this->currentServing->status) }}</div>
                @else
                    <div class="current-number">---</div>
                    <div style="font-size: 1.2rem; opacity: 0.9;">Tidak ada antrian aktif</div>
                @endif

                <div class="action-buttons">
                    <button wire:click="callNext" class="btn-staff btn-call">
                        <i class="fas fa-volume-up"></i> PANGGIL BERIKUTNYA
                    </button>
                    @if($this->currentServing)
                        <button wire:click="recall" class="btn-staff btn-recall">
                            <i class="fas fa-redo"></i> PANGGIL ULANG
                        </button>
                        <button wire:click="finish" class="btn-staff btn-finish">
                            <i class="fas fa-check"></i> SELESAI
                        </button>
                        <button wire:click="skip" class="btn-staff btn-skip">
                            <i class="fas fa-forward"></i> LEWATI
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="side-panel">
            <!-- Waiting List -->
            <div class="waiting-list">
                <h4 style="color: var(--accent-color); margin-bottom: 1rem;"><i class="fas fa-list-ol"></i> Daftar Tunggu ({{ count($this->waitingQueues) }})</h4>
                @forelse($this->waitingQueues as $q)
                    <div class="waiting-item">
                        <div style="font-weight: 700; color: var(--accent-color);">{{ $q->ticket_number }}</div>
                        <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $q->created_at->format('H:i') }}</div>
                    </div>
                @empty
                    <p style="font-size: 0.8rem; color: #ccc; text-align: center;">Kosong</p>
                @endforelse
            </div>

            <!-- Skipped List -->
            <div class="skipped-list" style="border-top: 4px solid #f44336;">
                <h4 style="color: #f44336; margin-bottom: 1rem;"><i class="fas fa-user-slash"></i> Terlewat ({{ count($this->skippedQueues) }})</h4>
                @forelse($this->skippedQueues as $s)
                    <div class="skipped-item">
                        <div style="font-weight: 700; color: #f44336;">{{ $s->ticket_number }}</div>
                        <button wire:click="recallSkipped({{ $s->id }})" class="btn-mini-call">PANGGIL</button>
                    </div>
                @empty
                    <p style="font-size: 0.8rem; color: #ccc; text-align: center;">Kosong</p>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        // Logic Suara Panggilan yang Lebih Natural
        window.addEventListener('announce-ticket', event => {
            const ticket = event.detail.ticket;
            const poli = event.detail.poli;
            
            const parts = ticket.split('-');
            const letter = parts[0];
            const number = parseInt(parts[1]);

            const message = `Panggilan untuk nomor, ${letter}, ${number}, silakan menuju, ${poli}`;
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.lang = 'id-ID';
            utterance.rate = 0.9;
            window.speechSynthesis.speak(utterance);
        });
    </script>
</div>
