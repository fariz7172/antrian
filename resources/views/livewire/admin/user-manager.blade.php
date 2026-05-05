<?php

use App\Models\User;
use App\Models\Poli;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    public $name, $email, $password, $role = 'staff', $poli_id;
    public $editingUserId = null;
    public $showForm = false;

    public function getUsersProperty()
    {
        return User::with('poli')->orderBy('role')->orderBy('name')->get();
    }

    public function getPolisProperty()
    {
        return Poli::active()->orderBy('name')->get();
    }

    public function openForm($id = null)
    {
        $this->reset(['name', 'email', 'password', 'role', 'poli_id', 'editingUserId']);
        
        if ($id) {
            $user = User::find($id);
            $this->editingUserId = $id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->role;
            $this->poli_id = $user->poli_id;
        }
        
        $this->showForm = true;
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->editingUserId,
            'role' => 'required|in:superadmin,staff',
            'poli_id' => 'required_if:role,staff',
        ];

        if (!$this->editingUserId) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'poli_id' => $this->role === 'staff' ? $this->poli_id : null,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        User::updateOrCreate(['id' => $this->editingUserId], $data);

        $this->showForm = false;
        $this->reset();
    }

    public function delete($id)
    {
        if ($id === auth()->id()) return; // Jangan hapus diri sendiri
        User::destroy($id);
    }
}; ?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 style="color: var(--accent-color); font-weight: 700; margin: 0;">Daftar Akun Staff</h3>
        <button wire:click="openForm" class="btn-staff btn-call" style="width: auto; padding: 10px 20px;">
            <i class="fas fa-user-plus"></i> Tambah Akun Baru
        </button>
    </div>

    @if($showForm)
    <div class="content-card" style="margin-bottom: 2rem; border-left: 5px solid var(--primary-color);">
        <h4 style="margin-top: 0;">{{ $editingUserId ? 'Edit Akun' : 'Tambah Akun Baru' }}</h4>
        <form wire:submit.prevent="save">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Nama Lengkap</label>
                    <input type="text" wire:model="name" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                    @error('name') <span style="color: red; font-size: 0.8rem;">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Email</label>
                    <input type="email" wire:model="email" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                    @error('email') <span style="color: red; font-size: 0.8rem;">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Password {{ $editingUserId ? '(Kosongkan jika tak diubah)' : '' }}</label>
                    <input type="password" wire:model="password" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                    @error('password') <span style="color: red; font-size: 0.8rem;">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Role</label>
                    <select wire:model.live="role" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                        <option value="staff">Staff / Dokter</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>
                @if($role === 'staff')
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Tugas di Poli</label>
                    <select wire:model="poli_id" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                        <option value="">Pilih Poli...</option>
                        @foreach($this->polis as $poli)
                            <option value="{{ $poli->id }}">{{ $poli->name }}</option>
                        @endforeach
                    </select>
                    @error('poli_id') <span style="color: red; font-size: 0.8rem;">{{ $message }}</span> @enderror
                </div>
                @endif
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-staff btn-finish" style="width: auto; padding: 10px 25px;">SIMPAN USER</button>
                <button type="button" wire:click="$set('showForm', false)" class="btn-staff btn-skip" style="width: auto; padding: 10px 25px;">BATAL</button>
            </div>
        </form>
    </div>
    @endif

    <div class="content-card">
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0 10px;">
                <thead>
                    <tr style="text-align: left; color: var(--text-muted); font-size: 0.9rem;">
                        <th style="padding: 1rem;">NAMA</th>
                        <th style="padding: 1rem;">EMAIL</th>
                        <th style="padding: 1rem;">ROLE</th>
                        <th style="padding: 1rem;">PENUGASAN</th>
                        <th style="padding: 1rem; text-align: right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->users as $user)
                    <tr style="background-color: var(--bg-color); border-radius: 15px;">
                        <td style="padding: 1rem; border-radius: 15px 0 0 15px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=random" style="width: 35px; height: 35px; border-radius: 50%;">
                                <div style="font-weight: 700;">{{ $user->name }}</div>
                            </div>
                        </td>
                        <td style="padding: 1rem; color: var(--text-muted);">{{ $user->email }}</td>
                        <td style="padding: 1rem;">
                            <span style="background: {{ $user->role === 'superadmin' ? '#346739' : '#91D06C' }}; color: {{ $user->role === 'superadmin' ? 'white' : '#346739' }}; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">
                                {{ strtoupper($user->role) }}
                            </span>
                        </td>
                        <td style="padding: 1rem;">
                            @if($user->role === 'staff')
                                <span style="font-weight: 600; color: var(--accent-color);">
                                    <i class="fas fa-hospital-user"></i> {{ $user->poli?->name ?? 'Belum Ditentukan' }}
                                </span>
                            @else
                                <span style="color: #ccc;">Akses Semua</span>
                            @endif
                        </td>
                        <td style="padding: 1rem; border-radius: 0 15px 15px 0; text-align: right;">
                            <button wire:click="openForm({{ $user->id }})" style="border: none; background: none; color: #1976d2; cursor: pointer; margin-right: 10px;"><i class="fas fa-edit"></i></button>
                            @if($user->id !== auth()->id())
                                <button wire:confirm="Hapus akun ini?" wire:click="delete({{ $user->id }})" style="border: none; background: none; color: #d32f2f; cursor: pointer;"><i class="fas fa-trash"></i></button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
