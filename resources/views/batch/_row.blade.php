{{-- One row of a stock entry. Used by the page and returned on its own when a row is added (no page reload). --}}
@php $baseUnit = $row->product->uom_dt->uom_unit ?? 'units'; @endphp
<tr data-id="{{ $row->id }}" data-url="{{ route('batch.updateOpenItem', $row) }}">
    <td class="js-no">{{ $index + 1 }}</td>
    <td>{{ $row->product->barcode ?? '' }}</td>
    <td>{{ $row->product->product_name ?? '' }}</td>
    @if($batch->status == 'Open')
        <td>
            <select class="form-select no-select2 js-unit">
                <option value="" data-base="1">{{ $row->product->uom_dt->uom_name ?? 'Unit' }}</option>
                @foreach($row->product->allUnits as $unit)
                    <option value="{{ $unit->id }}" data-base="{{ $unit->base_qty + 0 }}" @if($row->entry_unit_id == $unit->id) selected @endif>{{ $unit->name }} ({{ $unit->base_qty + 0 }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" class="form-control js-qty" min="0.01" step="any" value="{{ ($row->entry_qty ?? $row->quantity) + 0 }}"></td>
        <td><input type="number" class="form-control js-cost" min="0" step="0.01" value="{{ round($row->entry_cost ?? $row->cost_per_unit, 2) }}"></td>
        <td><input type="number" class="form-control js-total" min="0" step="0.01" value="{{ round($row->total_cost, 2) }}"></td>
        <td class="text-center js-base" data-unit="{{ $baseUnit }}">{{ $row->quantity + 0 }} {{ $baseUnit }} @ {{ money($row->cost_per_unit) }}</td>
    @else
        <td>{{ $row->entry_unit ?? ($row->product->uom_dt->uom_name ?? '') }}</td>
        <td class="text-center">{{ $row->entryLabel() }}</td>
        <td class="text-center">{{ money($row->entry_cost ?? $row->cost_per_unit) }}</td>
        <td class="text-center">{{ money($row->total_cost) }}</td>
        <td class="text-center">{{ $row->quantity + 0 }} {{ $baseUnit }} @ {{ money($row->cost_per_unit) }}</td>
    @endif
    <td class="text-center">{{ $row->balance + 0 }}</td>
    <td class="text-center">
        @if($batch->status == 'Open')
            <a style="color:red;cursor:pointer" title="Remove" class="js-remove" data-url="{{ route('batch.destroyItem', $row) }}"><i class="fa-solid fa-trash"></i></a>
        @else
            <a type="button" data-bs-toggle="modal" data-bs-target="#editModal{{ $row->id }}" title="Adjust"><i class="fa fa-pencil"></i></a>
        @endif
    </td>
</tr>
