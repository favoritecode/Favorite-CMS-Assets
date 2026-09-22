<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Engines;

use FavoriteCMS\Tools\Contracts\EngineInterface;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Support\EngineType;
use InvalidArgumentException;

class EngineResolver
{
    /**
     * @var array<string, EngineInterface>
     */
    protected array $engines = [];

    public function __construct(
        ?HtmlEngine $htmlEngine = null,
        ?CssEngine $cssEngine = null,
        ?JavaScriptEngine $jsEngine = null,
        ?PhpEngine $phpEngine = null,
        ?PythonApiEngine $pythonEngine = null
    ) {
        $this->engines[EngineType::HTML] = $htmlEngine ?? new HtmlEngine();
        $this->engines[EngineType::CSS] = $cssEngine ?? new CssEngine();
        $this->engines[EngineType::JAVASCRIPT] = $jsEngine ?? new JavaScriptEngine();
        $this->engines[EngineType::PHP] = $phpEngine ?? new PhpEngine();
        $this->engines[EngineType::PYTHON_API] = $pythonEngine ?? new PythonApiEngine();
    }

    public function setPythonServiceRepository(PythonServiceRepository $repo): void
    {
        $this->engines[EngineType::PYTHON_API] = new PythonApiEngine($repo);
    }

    public function registerEngine(string $type, EngineInterface $engine): void
    {
        $this->engines[strtoupper($type)] = $engine;
    }

    public function resolve(Tool|string $toolOrType): EngineInterface
    {
        if (is_string($toolOrType)) {
            $engineType = strtoupper(trim($toolOrType));
            if (isset($this->engines[$engineType])) {
                return $this->engines[$engineType];
            }
            throw new InvalidArgumentException("No execution engine registered for engine type '{$toolOrType}'.");
        }

        $engineType = strtoupper(trim($toolOrType->engine));

        if (isset($this->engines[$engineType])) {
            return $this->engines[$engineType];
        }

        foreach ($this->engines as $engine) {
            if ($engine->canHandle($toolOrType)) {
                return $engine;
            }
        }

        throw new InvalidArgumentException("No execution engine registered for engine type '{$toolOrType->engine}'.");
    }

    public function hasEngine(string $type): bool
    {
        return isset($this->engines[strtoupper(trim($type))]);
    }
}
