<?php

use App\Models\Poli;
use App\Models\Queue;
use Carbon\Carbon;
use Livewire\Volt\Component;

new class extends Component {
    public $polis;
    public $lastTicket = null;
    public $showSuccess = false;

    public function mount()
    {
        $this->polis = Poli::active()->get();
    }

    public function ambilAntrian($poliId)
    {
        $poli = Poli::find($poliId);
        
        // Ambil antrian terakhir hari ini untuk poli tersebut
        $lastQueue = Queue::where('poli_id', $poliId)
            ->whereDate('created_at', Carbon::today())
            ->orderBy('number', 'desc')
            ->first();

        $nextNumber = $lastQueue ? $lastQueue->number + 1 : 1;
        
        // Format nomor tiket (Contoh: A-001)
        $ticketNumber = $poli->code . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $queue = Queue::create([
            'poli_id' => $poliId,
            'number' => $nextNumber,
            'ticket_number' => $ticketNumber,
            'status' => 'waiting',
        ]);

        $this->lastTicket = [
            'number' => $ticketNumber,
            'poli' => $poli->name,
            'time' => $queue->created_at->format('H:i:s')
        ];

        $this->showSuccess = true;
        $this->dispatch('print-ticket');
    }

    public function closeSuccess()
    {
        $this->showSuccess = false;
    }
}; ?>

<div class="kiosk-wrapper">
    <style>
        .kiosk-wrapper {
            min-height: 100vh;
            background-color: #FFFBF1;
            padding: 3rem 1rem;
        }
        .header-kiosk {
            text-align: center;
            margin-bottom: 4rem;
        }
        .header-kiosk h1 {
            color: #346739;
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        .header-kiosk p {
            color: #4a5d4c;
            font-size: 1.2rem;
        }
        .poli-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        .poli-card {
            background: white;
            padding: 2.5rem;
            border-radius: 30px;
            box-shadow: 0 10px 30px rgba(52, 103, 57, 0.1);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .poli-card:hover {
            transform: translateY(-10px);
            border-color: #91D06C;
            box-shadow: 0 20px 40px rgba(52, 103, 57, 0.15);
        }
        .poli-icon {
            font-size: 4rem;
            color: #91D06C;
            margin-bottom: 1.5rem;
        }
        .poli-card h3 {
            color: #346739;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        .poli-card p {
            color: #888;
            font-size: 0.9rem;
        }

        /* Success Modal */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(26, 46, 28, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        .ticket-modal {
            background: white;
            width: 90%;
            max-width: 450px;
            padding: 3rem;
            border-radius: 40px;
            text-align: center;
            animation: slideUp 0.4s ease-out;
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .ticket-id {
            font-size: 4rem;
            font-weight: 900;
            color: #346739;
            margin: 1rem 0;
            letter-spacing: 2px;
        }
        .btn-close {
            background: #91D06C;
            color: #346739;
            padding: 1rem 2.5rem;
            border-radius: 20px;
            border: none;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            margin-top: 2rem;
            width: 100%;
        }

        /* CSS KHUSUS PRINT THERMAL */
        #print-area { 
            display: none; 
        }

        @media print {
            /* Sembunyikan semua elemen utama */
            .header-kiosk, .poli-grid, .modal-overlay, footer {
                display: none !important;
            }

            /* Tampilkan paksa area cetak */
            #print-area {
                display: block !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 58mm;
                margin: 0;
                padding: 0;
            }

            .thermal-ticket {
                width: 58mm;
                padding: 5px;
                box-sizing: border-box;
                font-family: 'Courier New', Courier, monospace;
                color: black;
                background: white;
            }

            /* Reset style body saat print */
            body, .kiosk-wrapper {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            @page {
                margin: 0;
                size: auto;
            }
        }
    </style>

    <div class="header-kiosk">
        <h1>SELAMAT DATANG</h1>
        <p>Silakan pilih Poli tujuan Anda untuk mengambil nomor antrian</p>
    </div>

    <div class="poli-grid">
        @foreach($polis as $poli)
            <div class="poli-card" wire:click="ambilAntrian({{ $poli->id }})">
                <div class="poli-icon">
                    <i class="{{ $poli->icon }}"></i>
                </div>
                <h3>{{ $poli->name }}</h3>
                <p>{{ $poli->description }}</p>
                <div style="margin-top: 1.5rem; color: #91D06C; font-weight: 700;">
                    KLIK UNTUK AMBIL TIKET
                </div>
            </div>
        @endforeach
    </div>

    @if($showSuccess)
        <div class="modal-overlay">
            <div class="ticket-modal">
                <i class="fas fa-check-circle" style="font-size: 4rem; color: #91D06C;"></i>
                <h2 style="margin-top: 1.5rem; color: #346739;">Tiket Berhasil Dicetak</h2>
                <p style="color: #666;">Nomor Antrian Anda:</p>
                <div class="ticket-id">{{ $lastTicket['number'] }}</div>
                <div style="background: #f8fcf9; padding: 1rem; border-radius: 20px; margin-top: 1rem;">
                    <div style="font-weight: 700; color: #346739;">{{ $lastTicket['poli'] }}</div>
                    <div style="font-size: 0.8rem; color: #888;">{{ $lastTicket['time'] }}</div>
                </div>
                <button class="btn-close" wire:click="closeSuccess">Selesai</button>
            </div>
        </div>
    @endif

    <!-- TEMPLATE TIKET UNTUK PRINTER THERMAL -->
    <div id="print-area">
        @if($lastTicket)
        <div class="thermal-ticket">
            <div style="text-align: center; margin-bottom: 5px;">
                <h3 style="margin: 0; font-size: 16px;">RUMAH SAKIT KITA</h3>
                <p style="margin: 0; font-size: 10px;">{{ $lastTicket['poli'] }}</p>
            </div>
            <div style="border-bottom: 1px dashed black; margin: 5px 0;"></div>
            <div style="text-align: center; margin: 10px 0;">
                <div style="font-size: 12px;">NOMOR ANTRIAN</div>
                <div style="font-size: 38px; font-weight: bold;">{{ $lastTicket['number'] }}</div>
            </div>
            <div style="border-bottom: 1px dashed black; margin: 5px 0;"></div>
            <div style="text-align: center; font-size: 10px;">
                <div>Waktu: {{ $lastTicket['time'] }}</div>
                <div style="margin-top: 10px; font-weight: bold;">MOHON MENUNGGU</div>
            </div>
        </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.on('print-ticket', (event) => {
                setTimeout(() => {
                    window.print();
                }, 500);
            });
        });
    </script>
</div>
