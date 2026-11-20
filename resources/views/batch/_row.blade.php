{{-- One row of a stock entry. Used by the page and returned on its own when a row is added (no page reload).
     data-label gives each cell its name when the table turns into cards on a phone. --}}
@php $baseUnit = $row->product->uom_dt->uom_unit ?? 'units'; @endphp
<tr data-id="{{ $row->id }}" data-url="{{ route('batch.updateOpenItem', $row) }}">
    <td class="js-no" data-label="No">{{ $index + 1 }}</td>
    <td data-label="Barcode">{{ $row->product->barcode ?? '' }}</td>
    <td data-label="Item" class="cell-item">{{ $row->product->product_name ?? 'Unknown item' }}@if($row->product?->trashed()) <span class="badge bg-label-danger">deleted</span>@endif</td>
    @if($batch->status == 'Open')
        <td data-label="Unit">
            <select class="form-select no-select2 js-unit">
                <option value="" data-base="1">{{ $row->product->uom_dt->uom_name ?? 'Unit' }}</option>
                @foreach($row->product->allUnits ?? [] as $unit)
                    <option value="{{ $unit->id }}" data-base="{{ $unit->base_qty + 0 }}" @if($row->entry_unit_id == $unit->id) selected @endif>{{ $unit->name }} ({{ $unit->base_qty + 0 }})</option>
                @endforeach
            </select>
        </td>
        <td data-label="Qty"><input type="number" inputmode="decimal" class="form-control js-qty" min="0.01" step="any" value="{{ ($row->entry_qty ?? $row->quantity) + 0 }}"></td>
        <td data-label="Cost per unit"><input type="number" inputmode="decimal" class="form-control js-cost" min="0" step="0.01" value="{{ round($row->entry_cost ?? $row->cost_per_unit, 2) }}"></td>
        <td data-label="Total cost"><input type="number" inputmode="decimal" class="form-control js-total" min="0" step="0.01" value="{{ round($row->total_cost, 2) }}"></td>
        <td data-label="In base units" class="text-center js-base" data-unit="{{ $baseUnit }}">{{ $row->quantity + 0 }} {{ $baseUnit }} @ {{ money($row->cost_per_unit) }}</td>
        <td data-label="Expiry date"><input type="date" class="form-control js-expiry" value="{{ $row->expiry_date ? \Carbon\Carbon::parse($row->expiry_date)->format('Y-m-d') : '' }}"></td>
    @else
        <td data-label="Unit">{{ $row->entry_unit ?? ($row->product->uom_dt->uom_name ?? '') }}</td>
        <td data-label="Qty" class="text-center">{{ $row->entryLabel() }}</td>
        <td data-label="Cost per unit" class="text-center">{{ money($row->entry_cost ?? $row->cost_per_unit) }}</td>
        <td data-label="Total cost" class="text-center">{{ money($row->total_cost) }}</td>
        <td data-label="In base units" class="text-center">{{ $row->quantity + 0 }} {{ $baseUnit }} @ {{ money($row->cost_per_unit) }}</td>
        <td data-label="Expiry date">
            {{-- the expiry date can still be fixed after the entry is completed; it does not change stock --}}
            <input type="date" class="form-control js-expiry-done" data-url="{{ route('batch.updateExpiry', $row) }}" value="{{ $row->expiry_date ? \Carbon\Carbon::parse($row->expiry_date)->format('Y-m-d') : '' }}">
        </td>
    @endif
    <td data-label="Balance" class="text-center">{{ $row->balance + 0 }}</td>
    <td data-label="Actions" class="text-center">
        @if($batch->status == 'Open')
            <a style="color:red;cursor:pointer" title="Remove" class="js-remove" data-url="{{ route('batch.destroyItem', $row) }}"><i class="fa-solid fa-trash"></i> <span class="d-md-none">Remove</span></a>
        @elseif(auth()->user()->role_id == 1)
            {{-- only the superadmin adjusts stock that was already added --}}
            <a type="button" data-bs-toggle="modal" data-bs-target="#editModal{{ $row->id }}" title="Adjust"><i class="fa fa-pencil"></i> <span class="d-md-none">Adjust</span></a>
        @endif
    </td>
</tr>
