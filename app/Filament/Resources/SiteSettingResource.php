<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'System Settings';
    protected static ?string $modelLabel = 'Landing Page Setting';
    protected static ?string $pluralModelLabel = 'Landing Page Settings';
    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false; // Only one row should exist
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Site Settings')
                    ->tabs([
                        // T1: Basic & Branding
                        Tabs\Tab::make('Branding & Contact')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Section::make('Website Information')->schema([
                                    Grid::make(['default' => 2])->schema([
                                        TextInput::make('site_name')
                                            ->label('Site Name (Appears in Header & Footer)')
                                            ->required(),
                                        TextInput::make('developer_name')
                                            ->label('Developer Name (Footer)')
                                            ->required(),
                                    ]),
                                    Textarea::make('footer_text')
                                        ->label('Footer Description Text')
                                        ->rows(3),
                                ]),
                                Section::make('Contact & Social Links')->schema([
                                    Grid::make(['default' => 2])->schema([
                                        TextInput::make('phone')->label('Contact Phone')->required(),
                                        TextInput::make('email')->label('Contact Email')->email()->required(),
                                        TextInput::make('facebook_link')->label('Facebook Link')->url(),
                                        TextInput::make('youtube_link')->label('YouTube Link')->url(),
                                    ]),
                                    TextInput::make('address')->label('Address'),
                                ])
                            ]),

                        // T2: Hero Section
                        Tabs\Tab::make('Hero Section')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Section::make('Main Hero Area (Home Page)')->schema([
                                    TextInput::make('hero_badge')
                                        ->label('Top Badge Text')
                                        ->helperText('Example: বাংলাদেশে এই প্রথম - Next Gen AI Sales')
                                        ->required(),
                                        
                                    Grid::make(['default' => 2])->schema([
                                        TextInput::make('hero_title_part1')
                                            ->label('Title First Line')
                                            ->helperText('Example: আপনার বিজনেসকে করুন')
                                            ->required(),
                                        TextInput::make('hero_title_part2')
                                            ->label('Title Second Line (Gradient)')
                                            ->helperText('Example: Automated Machine')
                                            ->required(),
                                    ]),
                                    
                                    Textarea::make('hero_subtitle')
                                        ->label('Hero Description')
                                        ->rows(4)
                                        ->required(),
                                ]),
                            ]),
                            
                        // T3: Dynamic Features & Pain Points
                        Tabs\Tab::make('Features & Pain Points')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Section::make('Pain Points (Manual System Flags)')->schema([
                                    Repeater::make('pain_points')
                                        ->label('Why Manual System is Bad')
                                        ->schema([
                                            TextInput::make('icon')->label('FontAwesome Icon Class')->helperText('e.g., fas fa-clock')->required(),
                                            TextInput::make('title')->label('Title')->required(),
                                            Textarea::make('desc')->label('Description')->rows(2)->required(),
                                        ])
                                        ->columns(['default' => 2])
                                        ->grid(['default' => 1])
                                        ->collapsible()
                                        ->defaultItems(3),
                                ]),
                                
                                Section::make('Core AI Features (Grid)')->schema([
                                    Repeater::make('features')
                                        ->label('NeuralCart Features')
                                        ->schema([
                                            Grid::make(['default' => 3])->schema([
                                                TextInput::make('icon')->label('FontAwesome Icon Class')->helperText('e.g., fas fa-robot')->required(),
                                                TextInput::make('title')->label('Feature Title')->required(),
                                                Select::make('color_class')->label('Color Theme')->options([
                                                    'blue' => 'Blue',
                                                    'purple' => 'Purple',
                                                    'green' => 'Green',
                                                    'orange' => 'Orange',
                                                    'pink' => 'Pink',
                                                    'cyan' => 'Cyan',
                                                ])->required(),
                                            ]),
                                            Textarea::make('desc')->label('Description')->rows(2)->required(),
                                        ])
                                        ->collapsible()
                                        ->defaultItems(6),
                                ]),
                            ]),
                            
                        // T4: Cost Savings
                        Tabs\Tab::make('Cost Comparison')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Cost Comparison Widget')
                                    ->description('Compare manual team vs AI costs')
                                    ->schema([
                                        Grid::make(['default' => 2])->schema([
                                            // Manual Side
                                            Section::make('Manual Human Team')->schema([
                                                TextInput::make('cost_comparison.manual_title')->label('Box Title')->default('Manual Human Team')->required(),
                                                TextInput::make('cost_comparison.manual_scenario')->label('Scenario Text')->default('Scenario A: ১৫ জন মডারেটর (৩ শিফট)')->required(),
                                                TextInput::make('cost_comparison.manual_salary')->label('Salary Amount')->default('১,৫০,০০০ ৳')->required(),
                                                TextInput::make('cost_comparison.manual_overhead')->label('Overhead Amount')->default('৮০,০০০ ৳')->required(),
                                                TextInput::make('cost_comparison.manual_loss')->label('Loss Amount')->default('২০,০০০ ৳')->required(),
                                                TextInput::make('cost_comparison.manual_total')->label('Total Monthly Cost')->default('২,৫০,০০০ ৳')->required(),
                                            ])->columnSpan(['default' => 1]),
                                            
                                            // AI Side
                                            Section::make('AI System')->schema([
                                                TextInput::make('cost_comparison.ai_title')->label('Box Title')->default('NeuralCart AI')->required(),
                                                TextInput::make('cost_comparison.ai_scenario')->label('Scenario Text')->default('Scenario B: Fully Automated (24/7)')->required(),
                                                TextInput::make('cost_comparison.ai_salary')->label('Salary Amount')->default('০ ৳ (Zero)')->required(),
                                                TextInput::make('cost_comparison.ai_capacity')->label('Capacity Text')->default('UNLIMITED')->required(),
                                                TextInput::make('cost_comparison.ai_accuracy')->label('Accuracy/Software Cost')->default('100% / <1 Sec Reply')->required(),
                                                TextInput::make('cost_comparison.ai_total')->label('Total Monthly Cost')->default('৫,০০০ - ১০,০০০ ৳')->required(),
                                            ])->columnSpan(['default' => 1]),
                                        ])
                                    ]),
                            ]),
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site_name')->label('Site Name'),
                Tables\Columns\TextColumn::make('phone')->label('Public Contact'),
                Tables\Columns\TextColumn::make('updated_at')->label('Last Updated')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteSettings::route('/'),
            'edit' => Pages\EditSiteSetting::route('/{record}/edit'),
        ];
    }
}
