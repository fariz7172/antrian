<?php

use App\Models\Poli;
use App\Models\Queue;
use Livewire\Volt\Component;

new class extends Component {
    public $lastCalledId = null;

    public function getActiveQueuesProperty()
    {
        // Ambil antrian terbaru yang sedang dipanggil/dilayani untuk setiap poli
        return Poli::active()->get()->map(function($poli) {
            $current = Queue::where('poli_id', $poli->id)
                ->whereIn('status', ['calling', 'serving'])
                ->whereDate('created_at', today())
                ->orderBy('called_at', 'desc')
                ->first();
                
            return [
                'poli_name' => $poli->name,
                'poli_code' => $poli->code,
                'ticket_number' => $current ? $current->ticket_number : '---',
                'status' => $current ? $current->status : 'idle',
                'id' => $current ? $current->id : null
            ];
        });
    }

    public function getLatestCallProperty()
    {
        return Queue::with('poli')
            ->where('status', 'calling')
            ->whereDate('created_at', today())
            ->orderBy('called_at', 'desc')
            ->first();
    }

    public function getSkippedQueuesProperty()
    {
        return Queue::where('status', 'skipped')
            ->whereDate('created_at', today())
            ->orderBy('updated_at', 'desc')
            ->take(8) // Ambil 8 nomor terlewat terbaru
            ->get();
    }

    public function updated()
    {
        $latest = $this->latestCall;
        if ($latest && $latest->id !== $this->lastCalledId) {
            $this->lastCalledId = $latest->id;
            $this->dispatch('announce-ticket', 
                ticket: $latest->ticket_number, 
                poli: $latest->poli->name
            );
        }
    }
}; ?>

<div wire:poll.3s class="display-container">
    <style>
        .display-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #346739 0%, #1a2e1c 100%);
            padding: 1.5rem;
            padding-bottom: 7rem; /* Memberi ruang ekstra untuk footer */
            color: white;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            overflow-x: hidden;
        }
        .display-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 1rem;
            flex-shrink: 0;
        }
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 1.5rem;
            flex-grow: 1;
            min-height: 0;
        }
        
        /* Left: Latest Call Focus */
        .latest-call-box {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
            text-align: center;
        }
        .latest-ticket {
            font-size: clamp(3rem, 15vw, 9rem);
            font-weight: 900;
            color: #91D06C;
            text-shadow: 0 10px 30px rgba(145, 208, 108, 0.3);
            margin: 0.5rem 0;
            line-height: 1;
        }
        
        /* Right: All Polis List */
        .polis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            align-content: start;
            overflow-y: auto;
            padding-right: 5px;
        }
        
        /* Custom Scrollbar for Polis Grid */
        .polis-grid::-webkit-scrollbar { width: 5px; }
        .polis-grid::-webkit-scrollbar-thumb { background: rgba(145, 208, 108, 0.3); border-radius: 10px; }

        /* Skipped Section */
        .skipped-section {
            margin-top: 1.5rem;
            background: rgba(244, 67, 54, 0.15);
            border: 2px solid rgba(244, 67, 54, 0.4);
            border-radius: 20px;
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-shrink: 0;
        }
        .skipped-label {
            background: #f44336;
            color: white;
            padding: 5px 15px;
            border-radius: 10px;
            font-weight: 800;
            white-space: nowrap;
        }
        .skipped-numbers {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            font-size: clamp(1rem, 4vw, 1.5rem);
            font-weight: 700;
            color: #ffcdd2;
        }

        .poli-display-card {
            background: white;
            border-radius: 25px;
            padding: 1.2rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #346739;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            border: 4px solid transparent;
            transition: all 0.3s ease;
        }
        .poli-display-card.active {
            border-color: #91D06C;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(145, 208, 108, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(145, 208, 108, 0); }
            100% { box-shadow: 0 0 0 0 rgba(145, 208, 108, 0); }
        }
        .poli-info h3 { font-size: 1.1rem; margin: 0; opacity: 0.8; }
        .poli-number { font-size: 2.2rem; font-weight: 900; color: #346739; }

        .footer-info {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: #91D06C;
            color: #346739;
            padding: 0.8rem 2rem;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            z-index: 100;
            box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
        }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .latest-ticket { font-size: clamp(3rem, 12vw, 7rem); }
            .poli-number { font-size: 2rem; }
        }

        @media (max-width: 992px) {
            .display-container {
                padding: 1rem;
                padding-bottom: 8rem;
                height: auto;
                min-height: 100vh;
            }
            .main-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .latest-call-box {
                padding: 2rem;
                border-radius: 25px;
            }
            .polis-grid {
                overflow-y: visible;
                grid-template-columns: 1fr; /* Force 1 column for tablet if it gets narrow */
            }
            .skipped-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding: 1rem;
            }
        }

        @media (min-width: 641px) and (max-width: 992px) {
            .polis-grid {
                grid-template-columns: repeat(2, 1fr); /* 2 columns for tablet */
            }
        }

        @media (max-width: 640px) {
            .display-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
                padding-bottom: 0.5rem;
            }
            .display-header h1 { font-size: 1.4rem !important; }
            #clock { font-size: 1.8rem !important; }
            
            .latest-ticket { font-size: 4.5rem; }
            .latest-call-box h3 { font-size: 1.6rem !important; }
            .latest-call-box { padding: 1.5rem; }
            
            .footer-info {
                flex-direction: column;
                text-align: center;
                gap: 5px;
                padding: 0.6rem 1rem;
                font-size: 0.75rem;
            }
            .polis-grid {
                grid-template-columns: 1fr; /* Strict 1 column for mobile */
            }
            .poli-display-card {
                padding: 1rem;
                border-radius: 20px;
            }
            .poli-number { font-size: 1.8rem; }
        }
    </style>

    <div class="display-header">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="background: #91D06C; width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #346739;">
                <i class="fas fa-hospital"></i>
            </div>
            <div>
                <h1 style="margin: 0; font-size: 2rem;">DISPLAY ANTRIAN</h1>
                <p style="margin: 0; opacity: 0.6;">Rumah Sakit Sehat Sejahtera</p>
            </div>
        </div>
        <div id="clock" style="font-size: 2.5rem; font-weight: 800; color: #91D06C;">{{ now()->format('H:i') }}</div>
    </div>

    <div class="main-grid">
        <!-- Latest Call Section -->
        <div class="latest-call-box">
            <h2 style="letter-spacing: 5px; opacity: 0.7;">PANGGILAN UTAMA</h2>
            @if($this->latestCall)
                <div class="latest-ticket">{{ $this->latestCall->ticket_number }}</div>
                <h3 style="font-size: 2.5rem; color: #91D06C;">{{ $this->latestCall->poli->name }}</h3>
            @else
                <div class="latest-ticket">---</div>
                <h3 style="font-size: 2rem; opacity: 0.5;">Menunggu Panggilan</h3>
            @endif
        </div>

        <!-- All Polis List -->
        <div class="polis-grid">
            @foreach($this->activeQueues as $p)
                <div class="poli-display-card {{ $p['status'] == 'calling' ? 'active' : '' }}">
                    <div class="poli-info">
                        <h3>{{ $p['poli_name'] }}</h3>
                        <div style="font-size: 0.9rem; font-weight: 600; text-transform: uppercase; color: #91D06C;">Loket {{ $p['poli_code'] }}</div>
                    </div>
                    <div class="poli-number">{{ $p['ticket_number'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    @if(count($this->skippedQueues) > 0)
    <div class="skipped-section">
        <div class="skipped-label"><i class="fas fa-user-slash"></i> TERLEWAT:</div>
        <div class="skipped-numbers">
            @foreach($this->skippedQueues as $s)
                <span>{{ $s->ticket_number }}</span>
                @if(!$loop->last) <span style="opacity: 0.3;">|</span> @endif
            @endforeach
        </div>
    </div>
    @endif

    <div class="footer-info">
        <div><i class="fas fa-bullhorn"></i> INFO: Mohon menunggu antrian Anda dengan tenang.</div>
        <div id="date-info">{{ now()->translatedFormat('l, d F Y') }}</div>
    </div>

    <script>
        // Logic Suara Panggilan yang Lebih Natural
        window.addEventListener('announce-ticket', event => {
            const ticket = event.detail.ticket; // Contoh: A-010
            const poli = event.detail.poli;
            
            // Pisahkan Huruf dan Angka (A-010 -> ["A", "010"])
            const parts = ticket.split('-');
            const letter = parts[0];
            const number = parseInt(parts[1]); // Menjadi 10 (integer)

            // Buat pesan yang natural
            const message = `Nomor antrian, ${letter}, ${number}, silakan menuju, ${poli}`;
            
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.lang = 'id-ID';
            utterance.rate = 0.9;
            utterance.pitch = 1;
            
            window.speechSynthesis.speak(utterance);
        });

        // Update Jam Real-time
        setInterval(() => {
            const now = new Date();
            const clock = document.getElementById('clock');
            if(clock) {
                clock.innerText = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
            }
        }, 1000);
    </script>
</div>
