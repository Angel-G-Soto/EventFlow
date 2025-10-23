<?php

namespace App\Livewire;

use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class EventRequestForm extends Component
{
    use WithFileUploads;

    // Form state
    public string $name = '';
    public string $email = '';
    public ?string $eventTitle = null;
    public bool $is_student_org = false;
    public string $advisor_email = '';
    public ?string $category = null;
    public string $starts_at = '';
    public string $ends_at = '';
    public string $location = '';
    public bool $needs_approval_doc = false;
    public $approval_pdf; // \Livewire\Features\SupportFileUploads\TemporaryUploadedFile

    protected function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'min:2', 'max:120'],
            'email'           => ['required', 'email', 'max:120'],
            'eventTitle'    => ['nullable', 'string', 'max:120'],
            'is_student_org'  => ['boolean'],
            'advisor_email'   => [
                Rule::requiredIf($this->is_student_org),
                'nullable', 'email', 'max:120',
            ],
            'category'        => ['nullable', 'in:meeting,workshop,competition,performance,other'],
            'starts_at'       => ['required', 'date'],
            'ends_at'         => ['required', 'date', 'after:starts_at'],
            'location'        => ['required', 'string', 'max:160'],
            'needs_approval_doc' => ['boolean'],
            'approval_pdf'    => [
                Rule::requiredIf($this->needs_approval_doc),
                'nullable', 'file', 'mimes:pdf', 'max:4096', // 4MB
            ],
        ];
    }

    public function updated($property): void
    {
        // real-time field-level validation
        $this->validateOnly($property, $this->rules());
    }

    public function save(): void
    {
        $validated = $this->validate();

        // TODO: persist to DB, dispatch event, etc.
        // Example (pseudo):
        // EventRequest::create([
        //     ...$validated,
        //     'approval_pdf_path' => $this->approval_pdf?->store('approvals', 'public'),
        // ]);

        // Reset form (keep some toggles if you prefer)
        $this->reset([
            'name','email','event_title','advisor_email','category',
            'starts_at','ends_at','location','approval_pdf',
        ]);
        $this->is_student_org = false;
        $this->needs_approval_doc = false;

        session()->flash('ok', 'Request submitted successfully!');
    }

    public function render()
    {
        return view('livewire.event-request-form')->layout('layouts.public');
    }
}
