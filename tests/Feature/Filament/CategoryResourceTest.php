<?php

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('can render the categories list page', function () {
    $this->get(CategoryResource::getUrl('index'))->assertSuccessful();
});

test('can list categories', function () {
    $categories = Category::factory()->count(3)->create();

    Livewire::test(ListCategories::class)
        ->assertCanSeeTableRecords($categories);
});

test('can search categories by name', function () {
    $categories = Category::factory()->count(3)->create();

    Livewire::test(ListCategories::class)
        ->searchTable($categories->first()->name)
        ->assertCanSeeTableRecords($categories->where('name', $categories->first()->name))
        ->assertCanNotSeeTableRecords($categories->where('name', '!=', $categories->first()->name));
});

test('can render the create category page', function () {
    $this->get(CategoryResource::getUrl('create'))->assertSuccessful();
});

test('can create a category', function () {
    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'Electronics',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Category::class, [
        'name' => 'Electronics',
    ]);
});

test('can validate required fields when creating a category', function () {
    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('can render the edit category page', function () {
    $category = Category::factory()->create();

    $this->get(CategoryResource::getUrl('edit', ['record' => $category]))->assertSuccessful();
});

test('can update a category', function () {
    $category = Category::factory()->create();

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Category',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->refresh()->name)->toBe('Updated Category');
});

test('can delete a category', function () {
    $category = Category::factory()->create();

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->callAction(\Filament\Actions\DeleteAction::class);

    $this->assertModelMissing($category);
});
