# scafera/frontend

Template rendering for the Scafera framework. Wraps a template engine internally — your code never touches it directly.

> **Provides:** Template rendering for Scafera — implements kernel's `ViewInterface` with a Twig-backed `render(string $template, array $context): string` that returns plain strings. Templates live in `templates/` at the project root. Controllers inject `ViewInterface`, never Twig directly.
>
> **Depends on:** A Scafera host project with a `templates/` directory at its root. Most users install `scafera/layered-web` (the meta-package) rather than requiring this directly.
>
> **Extension points:** None of its own — the package is a pure implementation of kernel's `ViewInterface`. New templates are picked up by dropping `.html.twig` files into `templates/`. Twig extensions for userland are explicitly not supported (ADR-030) — inject services and call their methods from controllers instead.
>
> **Not responsible for:** Form rendering (owned by `scafera/form`) · asset management (owned by `scafera/asset`) · Twig extensions for userland (ADR-030) · view composers / shared template data (inject services explicitly in controllers) · layout helpers (use Twig's native `{% extends %}` / `{% block %}`) · direct use of `Twig\*` types in userland (blocked by `TwigLeakageValidator`).

This is a **capability package**. It adds optional template rendering to a Scafera project. It does not define folder structure or architectural rules — those belong to architecture packages.

## Requirements

- PHP >= 8.4
- `scafera/kernel` ^1.0

## Installation

```bash
composer require scafera/frontend
```

> Most users won't install this directly. Instead, install a meta-package like `scafera/layered-web`, which pulls in the frontend along with architecture conventions.

## How it works

### The View contract

The kernel defines `ViewInterface` — the only type your code depends on:

```php
interface ViewInterface
{
    public function render(string $template, array $context = []): string;
}
```

This package provides the Twig-backed implementation. The bundle registers it automatically when installed — no configuration needed.

### Usage in controllers

Inject `ViewInterface` via constructor. The controller decides how to use the rendered string:

```php
use Scafera\Kernel\Contract\ViewInterface;
use Scafera\Kernel\Http\Response;
use Scafera\Kernel\Http\Route;

#[Route('/orders/{id}', methods: 'GET')]
final class Show
{
    public function __construct(
        private readonly ViewInterface $view,
        private readonly OrderService $orders,
    ) {}

    public function __invoke(Request $request): Response
    {
        $order = $this->orders->find($request->routeParam('id'));

        return new Response($this->view->render('order/show.html.twig', [
            'order' => $order,
        ]));
    }
}
```

### Template location

Templates live in `templates/` at your project root. The bundle configures this path automatically.

### Boundary enforcement

This package includes a `TwigLeakageValidator` that scans your `src/` directory for direct `Twig\*` imports. Violations are reported by `scafera validate`:

```
Package checks:
  ✗ No Twig imports in userland FAILED
    - src/Service/PdfGenerator.php: imports Twig types directly — use Scafera\Kernel\Contract\ViewInterface instead
```

## Public API

| Type | Class | Purpose |
|------|-------|---------|
| Contract | `Scafera\Kernel\Contract\ViewInterface` | Template rendering interface (defined in kernel) |
| Implementation | `Scafera\Frontend\View` | Twig-backed implementation (internal) |

User code should type-hint `ViewInterface`, never `View` directly.

## License

MIT
