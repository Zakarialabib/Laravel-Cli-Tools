<?php

namespace App\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class LivewireGeneratorCommand extends Command
{
    protected $signature = 'livewire:generate
                            {name : The name of the Livewire component}
                            {--crud : Generate CRUD operations}
                            {--model= : The name of the model to bind with the component}';

    protected $description = 'Generate a Livewire component';

    public function handle()
    {
        $name = $this->argument('name');
        $isCrud = $this->option('crud');
        $model = $this->option('model');

        // Generate Livewire component files
        Artisan::call('make:livewire', [
            'name' => $name,
        ]);

        $this->info("Livewire component '$name' generated successfully.");

        if ($isCrud) {
            // Generate CRUD operations
            $this->generateCrudOperations($name, $model);
        }
    }

    protected function generateCrudOperations($name, $model)
    {
        if (empty($model)) {
            $model = $this->ask('Please provide the name of the model to bind with the component');
        }

        // Generate migration file for the model
        Artisan::call('make:migration', [
            'name' => "create_{$model}_table",
            '--create' => strtolower($model).'s',
        ]);

        $this->info("Migration file for '$model' created successfully.");

        // Generate model file
        Artisan::call('make:model', [
            'name' => $model,
        ]);

        $this->info("Model '$model' created successfully.");

        // Generate views for CRUD operations
        $viewsPath = "livewire/{$name}";
        $viewsDirectory = resource_path("views/$viewsPath");

        File::makeDirectory($viewsDirectory, 0755, true);

        $views = ['index', 'create', 'edit', 'show'];

        foreach ($views as $view) {
            $stubPath = __DIR__."/stubs/{$view}.blade.stub";
            $viewPath = "$viewsDirectory/{$view}.blade.php";

            File::copy($stubPath, $viewPath);
        }

        $this->info("Views for CRUD operations created successfully.");

        // Bind the Livewire component with the model
        $this->bindComponentWithModel($name, $model);
    }

    protected function bindComponentWithModel($component, $model)
    {
        $componentClass = studly_case($component);
        $modelClass = studly_case($model);

        $componentPath = app_path("Http/Livewire/{$componentClass}.php");

        $contents = File::get($componentPath);

        $contents = str_replace("extends Component", "extends Model", $contents);
        $contents = str_replace("namespace App\Http\Livewire", "namespace App\Models", $contents);
        $contents = str_replace("'name'", "'$model'", $contents);

        File::put($componentPath, $contents);

        $this->info("Livewire component '$component' is now bound with model '$model'.");
    }
}
