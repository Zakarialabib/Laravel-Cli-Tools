<?php

namespace App\Commands;

use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CopyCommand extends Command
{
    protected $signature = 'copy-livewire {source : The source Laravel project path} {destination : The destination Laravel project path} {--folder= : The folder structure to copy from Livewire} {--views= : The path to Livewire views} {--model= : The name of the Livewire model to copy} {--replace : Replace existing files in the destination project} {--dry-run : Output the source and destination directory structures without copying any files}';

    protected $description = 'Copy Livewire components and folders from one Laravel project to another';

    public function handle()
    {
        $source = $this->argument('source');
        $destination = $this->argument('destination');
        $folder = $this->option('folder');
        $viewsPath = $this->option('views');
        $modelName = $this->option('model');
        $replace = $this->option('replace');
        $dryRun = $this->option('dry-run');

        // Validate the folder input
        if (empty($folder)) {
            $this->error('Please provide a valid folder structure using the --folder option.');
            return;
        }

        $sourcePath = $source . '/app/Http/Livewire/' . $folder;
        $destinationPath = $destination . '/app/Http/Livewire/' . $folder;

        // Confirm the copy operation with the user
        if (!$dryRun && !$this->confirmCopyOperation()) {
            return;
        }

        // Copy Livewire folders and files
        try {
            $this->copyLivewireFolder($sourcePath, $destinationPath, $replace, $dryRun, 'Livewire folder');

            // Copy Livewire views if provided
            if (!empty($viewsPath)) {
                $viewsSourcePath = $source . '/resources/views/livewire/' . $viewsPath;
                $viewsDestinationPath = $destination . '/resources/views/livewire/' . $viewsPath;

                if (is_dir($viewsSourcePath)) {
                    $this->copyLivewireFolder($viewsSourcePath, $viewsDestinationPath, $replace, $dryRun, 'Livewire views');
                } else {
                    $this->warn('The specified views path does not exist in the source project.');
                }
            }

            // Import the Livewire model if specified
            if (!empty($modelName)) {
                $modelSourcePath = $source . '/app/Models/' . $modelName . '.php';
                $modelDestinationPath = $destination . '/app/Models/' . $modelName . '.php';

                if (file_exists($modelSourcePath)) {
                    $this->copyLivewireFile($modelSourcePath, $modelDestinationPath, $replace, $dryRun, 'Livewire model');
                } else {
                    $this->warn('The specified Livewire model does not exist in the source project.');
                }
            }

        } catch (\Throwable $e) {
            $this->error('An error occurred while copying the Livewire components: ' . $e->getMessage());
        }
    }

    /**
     * Confirm the copy operation with the user.
     *
     * @return bool
     */
    protected function confirmCopyOperation()
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to copy the Livewire components? [y/N] ', false);

        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * Copy a Livewire folder and its contents.
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @param bool $replace
     * @param bool $dryRun
     * @param string|null $label
     * @return void
     */
    protected function copyLivewireFolder($sourcePath, $destinationPath, $replace, $dryRun, $label = null)
    {
        if (File::exists($destinationPath) && !$replace) {
            $this->warn('The specified ' . ($label ?: 'Livewire folder') . ' already exists in the destination project.');
        } else {
            if ($dryRun) {
                $this->outputDirectoryStructure($sourcePath, $destinationPath);
            } else {
                File::copyDirectory($sourcePath, $destinationPath);
                $this->info(ucfirst($label ?: 'Livewire folder') . ' copied successfully.');
            }
        }
    }

    /**
     * Copy a Livewire file.
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @param bool $replace
     * @param bool $dryRun
     * @param string|null $label
     * @return void
     */
    protected function copyLivewireFile($sourcePath, $destinationPath, $replace, $dryRun, $label = null)
    {
        if (File::exists($destinationPath) && !$replace) {
            $this->warn('The specified ' . ($label ?: 'Livewire file') . ' already exists in the destination project.');
        } else {
            if ($dryRun) {
                $this->outputFileContents($sourcePath, $destinationPath);
            } else {
                File::copy($sourcePath, $destinationPath);
                $this->info(ucfirst($label ?: 'Livewire file') . ' copied successfully.');
            }
        }
    }

    /**
     * Output the directory structure of a Livewire folder.
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @return void
     */
    protected function outputDirectoryStructure($sourcePath, $destinationPath)
    {
        $this->info('Source directory structure for Livewire folder:');
        $this->output->write(shell_exec('ls -R ' . $sourcePath));
        $this->info('Destination directory structure for Livewire folder: '. $this->option('folder'));
        $this->info('----------------------------------------');
        $this->output->write(shell_exec('ls -R ' . $destinationPath));
        $this->info('----------------------------------------');
    }

    /**
     * Output the contents of a Livewire file.
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @return void
     */
    protected function outputFileContents($destinationPath)
    {
        $this->info('Destination file for Livewire model: '.$this->option('model'));
        $this->info('----------------------------------------');
        $this->output->write(shell_exec('cat ' . $destinationPath));
        $this->info('----------------------------------------');
    }
}