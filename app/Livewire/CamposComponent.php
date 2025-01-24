<?php

namespace App\Livewire;

use App\Models\Campo;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class CamposComponent extends Component
{
    USE LivewireAlert;
    use WithPagination;
    protected $listeners = ['campaniaInsertada'=>'$refresh'];
    public function render()
    {
        $campos = Campo::orderBy('orden')->paginate(20);
        return view('livewire.campos-component',[
            'campos'=>$campos
        ]);
    }
}
