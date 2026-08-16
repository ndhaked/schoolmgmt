<?php

use App\Support\MarksheetBuilder;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.panel')] class extends Component
{
    public function with(): array
    {
        $student = auth()->user()->student;

        return array_merge(['student' => $student], MarksheetBuilder::build($student));
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900">My Marksheet</h1>
    </x-slot>

    <x-marksheet-card :student="$student" :rows="$rows" :summary="$summary" />
</div>
