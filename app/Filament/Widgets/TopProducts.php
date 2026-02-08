<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopProducts extends BaseWidget
{
    protected static ?string $heading = 'Top Products';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->whereHas('orders')
                    ->withSum('orders as total_quantity_sold', 'order_product.quantity')
                    ->orderByDesc('total_quantity_sold')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Product'),
                TextColumn::make('category.name')
                    ->label('Category'),
                TextColumn::make('total_quantity_sold')
                    ->label('Sold')
                    ->numeric(),
                TextColumn::make('price')
                    ->money(),
            ])
            ->paginated(false);
    }
}
