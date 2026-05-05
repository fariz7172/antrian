<?php

use App\Models\Poli;
use App\Models\Queue;
use Livewire\Volt\Component;

new class extends Component {
    public $selectedPoliId = null;
    
    // Patient Search & Form
    public $searchIdentifier = '';
    public $patient_id = null;
    public $nik = '';
    public $medical_record_number = '';
    public $name = '';
    public $birth_date = '';
    public $address = '';
    public $phone = '';
    public $is_new_patient = false;

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

    public function updatedSearchIdentifier()
    {
        if (strlen($this->searchIdentifier) < 3) {
            $this->resetPatientForm();
            return;
        }

        $patient = App\Models\Patient::where('nik', $this->searchIdentifier)
            ->orWhere('medical_record_number', $this->searchIdentifier)
            ->first();

        if ($patient) {
            $this->fillPatientData($patient);
            $this->is_new_patient = false;
        } else {
            $this->resetPatientForm();
            $this->is_new_patient = true;
            // Jika identifier looks like NIK, masukkan ke field NIK
            if (is_numeric($this->searchIdentifier) && strlen($this->searchIdentifier) >= 16) {
                $this->nik = $this->searchIdentifier;
            }
        }
    }

    private function fillPatientData($patient)
    {
        $this->patient_id = $patient->id;
        $this->nik = $patient->nik;
        $this->medical_record_number = $patient->medical_record_number;
        $this->name = $patient->name;
        $this->birth_date = $patient->birth_date;
        $this->address = $patient->address;
        $this->phone = $patient->phone;
    }

    private function resetPatientForm()
    {
        $this->patient_id = null;
        $this->nik = '';
        $this->medical_record_number = '';
        $this->name = '';
        $this->birth_date = '';
        $this->address = '';
        $this->phone = '';
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

        $queue = Queue::with('patient')->where('poli_id', $this->selectedPoliId)
            ->whereIn('status', ['calling', 'serving'])
            ->whereDate('created_at', today())
            ->orderBy('called_at', 'desc')
            ->first();

        if ($queue && $queue->patient && !$this->patient_id) {
            $this->fillPatientData($queue->patient);
        }

        return $queue;
    }

    public function callNext()
    {
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
            $this->resetPatientForm();
            $this->searchIdentifier = '';
            $this->dispatch('announce-ticket', ticket: $next->ticket_number, poli: $next->poli->name);
        }
    }

    public function savePatient()
    {
        if (!$this->currentServing) return;

        $data = [
            'nik' => $this->nik,
            'name' => $this->name,
            'birth_date' => $this->birth_date,
            'address' => $this->address,
            'phone' => $this->phone,
        ];

        if ($this->patient_id) {
            $patient = App\Models\Patient::find($this->patient_id);
            $patient->update($data);
        } else {
            // Generate Medical Record Number simple
            $data['medical_record_number'] = 'RM-' . date('Ymd') . '-' . rand(1000, 9999);
            $patient = App\Models\Patient::create($data);
            $this->patient_id = $patient->id;
            $this->medical_record_number = $patient->medical_record_number;
        }

        $this->currentServing->update([
            'patient_id' => $patient->id,
            'status' => 'serving'
        ]);

        session()->flash('patient_saved', 'Data pasien berhasil disimpan.');
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
            $this->resetPatientForm();
            $this->searchIdentifier = '';
            $this->dispatch('announce-ticket', ticket: $queue->ticket_number, poli: $queue->poli->name);
        }
    }

    public function finish()
    {
        if ($this->currentServing) {
            $this->currentServing->update(['status' => 'finished', 'finished_at' => now()]);
            $this->resetPatientForm();
            $this->searchIdentifier = '';
        }
    }

    public function skip()
    {
        if ($this->currentServing) {
            $this->currentServing->update(['status' => 'skipped']);
            $this->resetPatientForm();
            $this->searchIdentifier = '';
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
            gap: 1.5rem;
        }
        .poli-selector {
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            border: 2px solid var(--primary-color);
            background: var(--bg-color);
            font-weight: 600;
            color: var(--accent-color);
            outline: none;
            width: auto;
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
            font-size: clamp(4rem, 15vw, 8rem);
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-call { background: var(--primary-color); color: var(--accent-color); grid-column: span 3; font-size: 1.2rem; }
        .btn-recall { background: rgba(255,255,255,0.2); color: white; }
        .btn-finish { background: #4caf50; color: white; }
        .btn-skip { background: #f44336; color: white; }
        
        .patient-form-card {
            background: white;
            border-radius: 30px;
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-top: 2rem;
            border: 2px solid transparent;
            transition: var(--transition);
        }
        .patient-form-card.new-patient {
            border-color: var(--primary-color);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        .form-control {
            padding: 0.8rem 1rem;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            background: #fcfcfc;
            outline: none;
            transition: var(--transition);
        }
        .form-control:focus {
            border-color: var(--primary-color);
            background: white;
        }
        .search-container {
            position: relative;
            margin-bottom: 1rem;
        }
        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        
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
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr 300px;
                gap: 1.5rem;
            }
        }

        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .staff-header {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }
            .poli-selector {
                width: 100%;
            }
            .side-panel {
                flex-direction: row;
                gap: 1.5rem;
            }
            .waiting-list, .skipped-list {
                flex: 1;
            }
        }

        @media (max-width: 640px) {
            .side-panel {
                flex-direction: column;
            }
            .serving-card {
                padding: 2rem 1.5rem;
            }
            .action-buttons {
                grid-template-columns: 1fr;
            }
            .btn-call {
                grid-column: span 1;
            }
            .current-number {
                font-size: 5rem;
            }
            .staff-header h2 {
                font-size: 1.4rem;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
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

            @if($this->currentServing)
                <div class="patient-form-card">
                    <div class="card-header" style="margin-bottom: 1rem;">
                        <div>
                            <h3 style="color: var(--accent-color); margin: 0;">Identifikasi Pasien</h3>
                            <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0;">
                                Cari pasien terdaftar untuk dihubungkan ke antrian
                            </p>
                        </div>
                        @if($medical_record_number)
                            <div style="text-align: right;">
                                <div style="font-size: 0.7rem; color: var(--text-muted);">NO. REKAM MEDIS</div>
                                <div style="font-weight: 800; color: var(--accent-color);">{{ $medical_record_number }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="search-container">
                        <input type="text" wire:model.live.debounce.500ms="searchIdentifier" 
                               class="form-control" style="width: 100%;" 
                               placeholder="Masukkan NIK atau No. Rekam Medis...">
                        <i class="fas fa-search search-icon"></i>
                    </div>

                    @if($patient_id)
                        <div style="background: var(--bg-color); padding: 1.5rem; border-radius: 20px; border: 1px solid var(--primary-color);">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">NAMA PASIEN</div>
                                    <div style="font-weight: 700;">{{ $name }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.7rem; color: var(--text-muted);">NIK</div>
                                    <div style="font-weight: 700;">{{ $nik }}</div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 1.5rem;">
                                <button wire:click="savePatient" class="btn-primary" style="width: 100%;">
                                    <i class="fas fa-link"></i> HUBUNGKAN KE ANTRIAN
                                </button>
                            </div>
                        </div>
                    @elseif(strlen($searchIdentifier) >= 3)
                        <div style="text-align: center; padding: 2rem; background: #fff8e1; border-radius: 20px; border: 1px dashed #ffb300;">
                            <i class="fas fa-user-times" style="font-size: 2rem; color: #ffb300; margin-bottom: 1rem;"></i>
                            <p style="margin-bottom: 1rem; font-weight: 600;">Pasien tidak ditemukan</p>
                            <a href="{{ url('/admin/patients') }}" class="btn-primary" style="display: inline-block; text-decoration: none;">
                                <i class="fas fa-plus"></i> DAFTARKAN DI REKAM MEDIS
                            </a>
                        </div>
                    @endif

                    @if(session()->has('patient_saved'))
                        <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem; border-radius: 12px; margin-top: 1rem; font-size: 0.9rem; font-weight: 600; text-align: center;">
                            <i class="fas fa-check-circle"></i> {{ session('patient_saved') }}
                        </div>
                    @endif
                </div>
            @endif
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
