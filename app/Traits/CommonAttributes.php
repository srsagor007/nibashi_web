<?php

namespace App\Traits;

trait CommonAttributes
{
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    public function getStatusAttribute()
    {
        $class = $this->is_active ? 'success' : 'danger';
        $text = $this->is_active ? 'Active' : 'Inactive';

        return "<span class='badge badge-light-$class'>$text</span></span>";
    }

    public function getPublishedAttribute()
    {
        $class = $this->is_published ? 'primary' : 'warning';
        $text = $this->is_published ? 'Published' : 'Draft';

        return "<span class='badge badge-light-$class'>$text</span></span>";
    }

    public function toggleButton($url, $label = '')
    {
        $html = '<label class="form-check form-switch form-switch-sm form-check-custom form-check-solid" >';

        $html .= '<input class="form-check-input form-switch-input" name="toggle_input" type="checkbox" ' . ($this->is_active == 1 ? 'checked' : '') . ' data-target="' . $url . '">';

        if ($label) {
            $html .= '<span class="form-check-label text-muted">' . $label . '</span>';
        }
        $html .= '</label>';

        return $html;
    }

    public function editButton($url)
    {
        return '<a href="' . $url . '" class="btn btn-light-primary btn-icon btn-sm me-2" title="Edit"><i class="fas fa-edit"></i></a>';
    }

    public function viewButton($url)
    {
        return '<a href="' . $url . '" class="btn btn-light-info btn-icon btn-sm me-2" title="View"><i class="fas fa-eye"></i></a>';
    }

    public function deleteButton($url)
    {
        $formId = 'destroy-form-' . $this->id;

        return "<a class='btn btn-light-danger btn-icon btn-sm me-2' title='Delete' href='#'
                onclick='event.preventDefault(); confirmDelete(`{$formId}`);'><i class='fas fa-trash-alt'></i>
            </a>

            <form id='{$formId}' action='" . $url . "' method='POST' class='d-none'>
                <input type='hidden' name='_token' value='" . csrf_token() . "'>
                <input type='hidden' name='_method' value='DELETE'>
            </form>";
    }

}
