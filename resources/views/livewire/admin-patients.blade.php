<?php

use App\Models\Patient;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $patient_id, $nik, $medical_record_number, $name, $birth_date, $address, $phone;
    public $is_editing = false;

    protected $listeners = ['refreshPatients' => '$refresh'];

    public function getPatientsProperty()
    {
        return Patient::where('name', 'like', '%'.$this->search.'%')
            ->orWhere('nik', 'like', '%'.$this->search.'%')
            ->orWhere('medical_record_number', 'like', '%'.$this->search.'%')
            ->orderBy('name', 'asc')
            ->paginate(10);
    }

    public function edit($id)
    {
        $patient = Patient::findOrFail($id);
        $this->patient_id = $patient->id;
        $this->nik = $patient->nik;
        $this->medical_record_number = $patient->medical_record_number;
        $this->name = $patient->name;
        $this->birth_date = $patient->birth_date;
        $this->address = $patient->address;
        $this->phone = $patient->phone;
        $this->is_editing = true;
    }

    public function save()
    {
        $this->validate([
            'nik' => 'required|digits:16|unique:patients,nik,' . $this->patient_id,
            'name' => 'required|min:3',
            'birth_date' => 'required|date',
        ]);

        $data = [
            'nik' => $this->nik,
            'name' => $this->name,
            'birth_date' => $this->birth_date,
            'address' => $this->address,
            'phone' => $this->phone,
        ];

        if ($this->is_editing) {
            Patient::find($this->patient_id)->update($data);
            session()->flash('success', 'Data pasien berhasil diperbarui.');
        } else {
            $data['medical_record_number'] = 'RM-' . date('Ymd') . '-' . rand(1000, 9999);
            Patient::create($data);
            session()->flash('success', 'Pasien baru berhasil didaftarkan.');
        }

        $this->resetForm();
    }

    public function resetForm()
    {
        $this->reset(['patient_id', 'nik', 'medical_record_number', 'name', 'birth_date', 'address', 'phone', 'is_editing']);
    }
}; ?>

<div>
    <style>
        .premium-card {
            background: #ffffff;
            border-radius: 28px;
            padding: 2.5rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
        }
        .premium-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #4a5568;
            margin-bottom: 0.6rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .modern-input {
            width: 100%;
            padding: 1rem 1.25rem;
            background: #f8fafc;
            border: 2px solid #f1f5f9;
            border-radius: 16px;
            font-size: 1rem;
            color: #1e293b;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .modern-input:focus {
            background: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(145, 208, 108, 0.15);
            outline: none;
        }
        .btn-premium {
            background: var(--accent-color);
            color: white;
            padding: 1rem 2rem;
            border-radius: 16px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(0, 0, 0, 0.15);
            filter: brightness(1.1);
        }
        .search-wrapper {
            position: relative;
            width: 350px;
        }
        .search-wrapper input {
            padding-left: 3rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }
        .search-wrapper .search-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }
            .premium-card {
                padding: 1.5rem;
            }
            .search-wrapper {
                width: 100%;
            }
            .btn-premium {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="search-section" style="margin-bottom: 2rem;">
        <div class="premium-card" style="padding: 1.5rem 2rem; border-top: none; border-left: 4px solid var(--primary-color);">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;">
                <div>
                    <h4 style="margin: 0; color: var(--accent-color); font-weight: 700;">Cari Data Pasien</h4>
                    <p style="margin: 0; font-size: 0.8rem; color: #64748b;">Temukan rekam medis pasien berdasarkan Nama, NIK, atau No. RM</p>
                </div>
                <div class="search-wrapper" style="width: 100%; max-width: 500px;">
                    <div class="search-container" style="margin: 0; width: 100%;">
                        <input type="text" wire:model.live.debounce.300ms="search" class="modern-input" style="padding-left: 3.5rem;" placeholder="Ketik Nama, NIK, atau Nomor Rekam Medis...">
                        <i class="fas fa-search search-icon" style="left: 1.5rem; font-size: 1.1rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="premium-card">
        <div class="card-header" style="border: none; margin-bottom: 2rem;">
            <div>
                <h2 style="color: var(--accent-color); font-weight: 800; font-size: 1.5rem; margin: 0;">
                    <i class="fas {{ $is_editing ? 'fa-user-edit' : 'fa-user-plus' }}" style="margin-right: 10px; opacity: 0.5;"></i> 
                    {{ $is_editing ? 'Update Rekam Medis' : 'Registrasi Pasien Baru' }}
                </h2>
                <p style="color: #64748b; margin-top: 5px; font-size: 0.9rem;">
                    {{ $is_editing ? 'Lakukan perubahan pada data pasien yang sudah terdaftar' : 'Masukkan data diri lengkap pasien untuk memulai rekam medis' }}
                </p>
            </div>
            @if($is_editing)
                <button wire:click="resetForm" class="btn-premium" style="background: #f1f5f9; color: #64748b; box-shadow: none;">
                    <i class="fas fa-times"></i> Batal
                </button>
            @endif
        </div>

        <form wire:submit.prevent="save">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nomor Induk Kependudukan (NIK)</label>
                    <input type="text" wire:model="nik" class="modern-input" placeholder="Contoh: 3275xxxxxxxxxxxx">
                    @error('nik') <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label>Nama Pasien Sesuai Identitas</label>
                    <input type="text" wire:model="name" class="modern-input" placeholder="Masukkan nama lengkap">
                    @error('name') <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" wire:model="birth_date" class="modern-input">
                    @error('birth_date') <span style="color: #ef4444; font-size: 0.8rem; margin-top: 5px; display: block;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label>Nomor Telepon / WhatsApp</label>
                    <input type="text" wire:model="phone" class="modern-input" placeholder="Contoh: 08123456789">
                </div>
                <div class="form-group" style="grid-column: span 1 / -1;">
                    <label>Alamat Domisili Saat Ini</label>
                    <textarea wire:model="address" class="modern-input" rows="3" placeholder="Masukkan alamat lengkap (Jalan, No. Rumah, RT/RW)"></textarea>
                </div>
            </div>
            
            <div style="margin-top: 3rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-premium">
                    <i class="fas fa-check-circle"></i> 
                    {{ $is_editing ? 'Simpan Perubahan Data' : 'Konfirmasi & Daftar Pasien' }}
                </button>
            </div>
        </form>
    </div>

    <div class="content-card" style="margin-top: 2.5rem; border-radius: 28px; padding: 2rem;">
        <div class="card-header" style="margin-bottom: 1.5rem;">
            <h3 style="color: var(--accent-color); font-weight: 700; margin: 0;">
                <i class="fas fa-users" style="opacity: 0.5; margin-right: 10px;"></i> Daftar Pasien Terdaftar
            </h3>
            <div style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">
                Total: {{ $this->patients->total() }} Pasien
            </div>
        </div>

        @if(session()->has('success'))
            <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 600;">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        <div class="table-responsive">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 12px;">
                <thead>
                    <tr style="text-align: left; color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">
                        <th style="padding: 0 1.2rem;">No. RM</th>
                        <th style="padding: 0 1.2rem;">Nama Pasien</th>
                        <th style="padding: 0 1.2rem;">NIK</th>
                        <th style="padding: 0 1.2rem;">Tgl Lahir</th>
                        <th style="padding: 0 1.2rem; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->patients as $p)
                    <tr wire:key="patient-{{ $p->id }}" style="background-color: #f8fafc; transition: all 0.2s ease;">
                        <td style="padding: 1.2rem; font-weight: 800; color: var(--accent-color); border-radius: 16px 0 0 16px;">{{ $p->medical_record_number }}</td>
                        <td style="padding: 1.2rem; font-weight: 700; color: #1e293b;">{{ $p->name }}</td>
                        <td style="padding: 1.2rem; color: #64748b; font-family: monospace;">{{ $p->nik }}</td>
                        <td style="padding: 1.2rem; color: #64748b;">{{ $p->birth_date }}</td>
                        <td style="padding: 1.2rem; border-radius: 0 16px 16px 0; text-align: center;">
                            <button wire:click="edit({{ $p->id }})" class="btn-mini-call" style="background: #e2e8f0; color: #475569; padding: 8px 16px;">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 4rem; color: #cbd5e1;">
                            <i class="fas fa-folder-open" style="font-size: 3rem; display: block; margin-bottom: 1rem; opacity: 0.3;"></i>
                            Data pasien tidak ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1.5rem;">
            {{ $this->patients->links() }}
        </div>
    </div>
</div>
