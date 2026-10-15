@php
    $columns = $this->presenter->columns();
    $sortOptions = $this->presenter->sortOptions();
    $sortIsDefault = $this->presenter->sortIsDefault($sort, $sortDir);
@endphp
<x-ui.table.tools>
    <x-ui.table.density-toggle/>
    <x-ui.table.sort-popover :options="$sortOptions" :sort="$sort" :sortDir="$sortDir" :isDefault="$sortIsDefault"/>
    <x-ui.table.col-toggle :scope="'dms'" :columns="$columns"/>
    <x-ui.forms.date-span-popover :active="$this->dateSpanActive()" :currentBasis="$dateBasis"
                                 :endYear="\Morilog\Jalali\Jalalian::now()->getYear() + 1"/>
</x-ui.table.tools>
