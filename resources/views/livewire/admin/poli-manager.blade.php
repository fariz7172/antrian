<?php

use App\Models\Poli;
use Livewire\Volt\Component;

new class extends Component {
    public $name, $code, $icon = 'fa-user-md', $is_active = true;
    public $editingPoliId = null;
    public $showForm = false;

    public function getPolisProperty()
    {
        return Poli::orderBy('name')->get();
    }

    public function openForm($id = null)
    {
        $this->reset(['name', 'code', 'icon', 'is_active', 'editingPoliId']);
        
        if ($id) {
            $poli = Poli::find($id);
            $this->editingPoliId = $id;
            $this->name = $poli->name;
            $this->code = $poli->code;
            $this->icon = $poli->icon;
            $this->is_active = $poli->is_active;
        }
        
        $this->showForm = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:5|unique:polis,code,' . $this->editingPoliId,
            'icon' => 'required',
        ]);

        Poli::updateOrCreate(
            ['id' => $this->editingPoliId],
            [
                'name' => $this->name,
                'code' => strtoupper($this->code),
                'icon' => $this->icon,
                'is_active' => $this->is_active
            ]
        );

        $this->showForm = false;
        $this->reset();
    }

    public function delete($id)
    {
        Poli::destroy($id);
    }

    public function toggleStatus($id)
    {
        $poli = Poli::find($id);
        $poli->update(['is_active' => !$poli->is_active]);
    }
}; ?>

<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 style="color: var(--accent-color); font-weight: 700; margin: 0;">Daftar Poli</h3>
        <button wire:click="openForm" class="btn-staff btn-call" style="width: auto; padding: 10px 20px;">
            <i class="fas fa-plus"></i> Tambah Poli Baru
        </button>
    </div>

    @if($showForm)
    <div class="content-card" style="margin-bottom: 2rem; border-left: 5px solid var(--primary-color);">
        <h4 style="margin-top: 0;">{{ $editingPoliId ? 'Edit Poli' : 'Tambah Poli Baru' }}</h4>
        <form wire:submit.prevent="save">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Nama Poli</label>
                    <input type="text" wire:model="name" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                    @error('name') <span style="color: red; font-size: 0.8rem;">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Kode (Max 5 Huruf)</label>
                    <input type="text" wire:model="code" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;" placeholder="MISAL: UMU">
                    @error('code') <span style="color: red; font-size: 0.8rem;">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; margin-bottom: 5px; font-weight: 600;">Icon (FontAwesome)</label>
                    <input type="text" wire:model="icon" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                </div>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn-staff btn-finish" style="width: auto; padding: 10px 25px;">SIMPAN</button>
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
                        <th style="padding: 1rem;">ICON</th>
                        <th style="padding: 1rem;">NAMA POLI</th>
                        <th style="padding: 1rem;">KODE</th>
                        <th style="padding: 1rem;">STATUS</th>
                        <th style="padding: 1rem; text-align: right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->polis as $poli)
                    <tr style="background-color: var(--bg-color); border-radius: 15px;">
                        <td style="padding: 1rem; border-radius: 15px 0 0 15px;">
                            <div style="width: 40px; height: 40px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary-color);">
                                <i class="fas {{ $poli->icon }}"></i>
                            </div>
                        </td>
                        <td style="padding: 1rem; font-weight: 700;">{{ $poli->name }}</td>
                        <td style="padding: 1rem;"><code style="background: #eee; padding: 2px 8px; border-radius: 5px;">{{ $poli->code }}</code></td>
                        <td style="padding: 1rem;">
                            <button wire:click="toggleStatus({{ $poli->id }})" 
                                style="border: none; background: {{ $poli->is_active ? '#e8f5e9' : '#ffebee' }}; color: {{ $poli->is_active ? '#2e7d32' : '#c62828' }}; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; cursor: pointer;">
                                {{ $poli->is_active ? 'AKTIF' : 'NON-AKTIF' }}
                            </button>
                        </td>
                        <td style="padding: 1rem; border-radius: 0 15px 15px 0; text-align: right;">
                            <button wire:click="openForm({{ $poli->id }})" style="border: none; background: none; color: #1976d2; cursor: pointer; margin-right: 10px;"><i class="fas fa-edit"></i></button>
                            <button wire:confirm="Hapus poli ini?" wire:click="delete({{ $poli->id }})" style="border: none; background: none; color: #d32f2f; cursor: pointer;"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
