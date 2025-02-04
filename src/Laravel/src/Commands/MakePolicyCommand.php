<?php

declare(strict_types=1);

namespace MoonShine\Laravel\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;

use function Laravel\Prompts\outro;
use function Laravel\Prompts\suggest;

use MoonShine\Laravel\MoonShineAuth;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Finder\Finder;

#[AsCommand(name: 'moonshine:policy')]
class MakePolicyCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:policy {className?}';

    protected $description = 'Create policy for Model';

    /**
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $modelPath = is_dir(app_path('Models')) ? app_path('Models') : app_path();

        if (! $className = $this->argument('className')) {
            $className = suggest(
                label: 'Model',
                options: collect((new Finder())->files()->depth(0)->in($modelPath))
                    ->map(static fn ($file) => $file->getBasename('.php'))
                    ->values()
                    ->all(),
                required: true,
            );
        }

        $model = $this->qualifyModel($className);
        $className = class_basename($model) . "Policy";

        $path = app_path("/Policies/$className.php");

        if (! is_dir(app_path('/Policies'))) {
            $this->makeDir(app_path('/Policies'));
        }

        $this->copyStub('Policy', $path, [
            'DummyClass' => $className,
            '{model-namespace}' => $model,
            '{model}' => class_basename($model),
            '{user-model-namespace}' => MoonShineAuth::getModel()::class,
            '{user-model}' => class_basename(MoonShineAuth::getModel()),
        ]);

        outro(
            "$className was created: " . str_replace(
                base_path(),
                '',
                $path
            )
        );

        return self::SUCCESS;
    }
}
