# erikwang2013/hashids

**Languages:** [中文](../../../README.md) · [English](../en/README.md) · [한국어](../ko/README.md) · [Русский](../ru/README.md) · [Deutsch](../de/README.md) · [Français](../fr/README.md) · **Español** · [Português](../pt/README.md) · [हिन्दी](../hi/README.md) · [العربية](../ar/README.md) · [বাংলা](../bn/README.md) · [Bahasa Indonesia](../id/README.md) · [日本語](../ja/README.md)

<p align="center">
  <img src="../../mascot.svg" alt="Hashy — mascota del proyecto erikwang2013/hashids" width="200" />
</p>

<p align="center"><strong>Hashy</strong> — la mascota del proyecto; el <code>#</code> de su pecho es su seña de identidad</p>

Convierte los ID autoincrementales de la base de datos en cadenas cortas e imposibles de adivinar, **con una sola API que corre a la vez en Laravel, Webman, ThinkPHP, Hyperf, Yii 2 y Yii 3**.

Se apoya en [`hashids/hashids`](https://github.com/vinkla/hashids) v5; la configuración y el uso siguen a [**vinkla/hashids**](https://github.com/vinkla/laravel-hashids) (múltiples conexiones, conexión por defecto, `HashidsManager` + fábrica), por lo que la sustitución es transparente.

## Descripción del proyecto

**Hashids** es un generador de ID cortos que codifica ID numéricos (como las claves primarias de una base de datos) en cadenas cortas, únicas e imposibles de adivinar. A diferencia de los UUID o los ID de tipo snowflake, Hashids encaja mejor en escenarios de cara al usuario (URL, códigos para compartir, números de pedido, etc.): oculta el número original sin dejar de ser corto y legible.

Este paquete, `erikwang2013/hashids`, es la **capa de integración multientorno PHP** de Hashids: su diseño toma como referencia y se alinea con el estilo de API de [vinkla/hashids](https://github.com/vinkla/laravel-hashids) (múltiples conexiones, conexión por defecto, patrón Manager + Factory) y amplía la compatibilidad a otros frameworks PHP de uso habitual en China.

**Características principales:**

- **Compatibilidad multientorno**: la misma API sirve a la vez para Laravel, Webman, ThinkPHP, Hyperf, Yii 2 y Yii 3, con un coste de migración mínimo.
- **Soporte multiconexión**: una aplicación puede configurar varias combinaciones de Salt/Length a la vez (por ejemplo, sales distintas para el ID de usuario y el de pedido) y cambiar entre ellas con `connection('xxx')`.
- **Sin dependencia de frameworks**: se puede usar por sí solo sin depender de ningún framework concreto; basta con `new HashidsManager($config, $factory)`.
- **Alineado con vinkla/hashids**: en Laravel, el Facade, el enlace en el contenedor y el formato de `config/hashids.php` son idénticos a vinkla/hashids, por lo que la sustitución es transparente.
- **Estilo nativo de cada framework**: cada integración sigue las convenciones de su framework: Laravel usa ServiceProvider + Facade, Webman usa Plugin + Bootstrap, ThinkPHP usa Service, Hyperf usa ConfigProvider, Yii 2 usa Bootstrap + componente de aplicación e Yii 3 usa ServiceProvider.

**Casos de uso:**

| Escenario | Descripción |
|------|------|
| Ocultar ID autoincrementales | Asigna `user_id=100` a `/user/3kTMd` y evita revelar la escala del negocio |
| Enlaces cortos y códigos para compartir | Más cortos que un UUID y más controlables que una cadena aleatoria |
| Números de pedido / de operación | Buena legibilidad, lo que facilita la atención al cliente y la revisión de registros |
| Aislamiento multiinquilino / multimódulo | Cada conexión usa un Salt distinto, de modo que los espacios de codificación son independientes entre sí |

**Advertencias:**

- Hashids es **codificación (encode/decode), no cifrado**. El Salt solo dificulta las conjeturas: no lo uses en escenarios sensibles para la seguridad (como tokens o contraseñas).
- Si cambias el Salt o el Length una vez en producción, todos los ID ya codificados dejarán de ser válidos; planifícalo con antelación y fija la configuración.
- **Un Salt vacío equivale a no tener protección**: con un salt vacío el resultado de la codificación es enumerable (`encode(1)`, `encode(2)`… el orden es predecible). Si lo escribes como
  `env('HASHIDS_SALT', '')`, olvidar la variable de entorno no da error ni aviso: simplemente degrada en silencio a un salt vacío, así que confirma que el salt está configurado antes de publicar.
- **En frameworks de memoria persistente (Webman / Hyperf), cambiar la configuración exige reiniciar el proceso**: `HashidsManager` toma una instantánea de la configuración al construirse y cachea las conexiones de forma permanente,
  así que editar `config/hashids.php` (o cambiar el salt desde un centro de configuración) no surte efecto en caliente; solo tras un `reload` o un reinicio.

## Estructura del proyecto

```
hashids/
├── src/
│   ├── HashidsManager.php                     # Núcleo: resolución multiconexión, caché de instancias, proxy a la conexión por defecto
│   ├── HashidsFactory.php                     # Núcleo: crea instancias de Hashids desde la config de conexión
│   ├── Mascot.php                             # Versión ASCII de la mascota del proyecto, "Hashy"
│   ├── Install.php                            # Enganches de instalación / desinstalación de Webman
│   ├── Laravel/
│   │   ├── HashidsServiceProvider.php         # Singleton del contenedor + alias + publicación de config
│   │   └── Facades/Hashids.php                # Facade (conexión por defecto)
│   ├── Webman/
│   │   └── Bootstrap.php                      # Registra las definiciones del contenedor al arrancar el proceso
│   ├── ThinkPHP/
│   │   └── HashidsService.php                 # Registro y enlace vía think\Service
│   ├── Hyperf/
│   │   ├── ConfigProvider.php                 # Mapa de dependencias + publicación de config
│   │   ├── HashidsManagerFactory.php
│   │   └── HashidsClientFactory.php
│   ├── Yii2/
│   │   ├── Bootstrap.php                      # BootstrapInterface: registra Yii::$container
│   │   └── Component.php                      # Componente de aplicación: Yii::$app->hashids
│   ├── Yii3/
│   │   └── ServiceProvider.php                # ServiceProviderInterface: devuelve seis definiciones
│   └── config/plugin/erikwang2013/hashids/    # Esqueleto del plugin de Webman (se copia al proyecto al instalar)
│       ├── app.php                            # Interruptor enable
│       └── bootstrap.php                      # Registra Webman\Bootstrap
├── config/
│   ├── hashids.php                            # Config plana: Laravel / Webman / ThinkPHP
│   └── autoload/hashids.php                   # Config de Hyperf (misma estructura plana, otra ruta)
├── tests/
│   ├── HashidsManagerTest.php                 # Comportamiento del núcleo
│   ├── HashidsFactoryTest.php
│   ├── InstallTest.php
│   ├── MascotTest.php                         # Mascota del proyecto: alineación de glifos y saludo
│   ├── Laravel/ · Webman/ · ThinkPHP/ · Hyperf/ · Yii2/ · Yii3/   # Pruebas de adaptación por framework
│   ├── Config/PluginConfigTest.php
│   ├── Contract/                              # Pruebas de contrato con frameworks reales (job de CI aparte, ver más abajo)
│   └── Support/FrameworkStubs.php             # Dobles de clases de framework para las pruebas
├── docs/
│   ├── mascot.svg                             # La mascota del proyecto, "Hashy"
│   ├── architecture.svg                       # Diagrama de arquitectura
│   ├── features.svg                           # Diagrama de funcionalidades
│   └── lifecycle.svg                          # Diagrama del ciclo de vida
├── .github/workflows/release.yml              # Crea el tag y publica la release al hacer push a main
├── composer.json
└── phpunit.xml.dist
```

> No se listan los artefactos de dependencias como `vendor/` o `composer.lock`; `src/config/` es el **esqueleto del plugin** y `config/` es la **configuración de ejemplo publicable**: son dos cosas distintas.

## Diseño de la arquitectura

![Diseño de la arquitectura](./architecture.svg)

**Cuatro capas con dependencia unidireccional**: cada capa superior depende de la inferior, nunca al revés:

| Capa | Responsabilidad | Ubicación |
|----|------|------|
| Capa de llamada desde la aplicación | Tres puntos de entrada: Facade, contenedor / funciones auxiliares e inyección por constructor | Código de negocio |
| Capa de adaptación de frameworks | Solo cablea: enlace en el contenedor, lectura de config, publicación de config | `src/<Framework>/` |
| Capa del núcleo | Gestión multiconexión y construcción de instancias, **sin depender de ningún framework** | `src/HashidsManager.php`, `src/HashidsFactory.php` |
| Dependencia de base | La implementación real de codificación y decodificación | `hashids/hashids` |

La capa del núcleo es el corazón del paquete: `HashidsManager` guarda la configuración, `connection($name)` construye y cachea conexiones bajo demanda y `__call()` redirige a la conexión por defecto las llamadas a métodos en las que no se indica ninguna; `HashidsFactory::make()` es el único sitio donde se construye un `Hashids\Hashids`. Las clases de adaptación de los seis frameworks suman unas 490 líneas (Facade incluido, las dos fábricas de Hyperf y el componente de aplicación de Yii 2): su única misión es enchufar el núcleo al contenedor de cada uno.

## Diseño de funcionalidades

![Diseño de funcionalidades](./features.svg)

Seis grupos de capacidades, todos alrededor del mismo núcleo:

- **API de codificación y decodificación**: `encode()` / `decode()` / `encodeHex()` / `decodeHex()`; pasan por `__call()` hasta la conexión por defecto, sin necesidad de un `connection()` explícito.
- **Gestión multiconexión**: se cambia con `connection('alternative')`; la construcción es perezosa y cada conexión se construye una sola vez.
- **Adaptación multientorno**: Laravel usa `ServiceProvider`, Webman usa `Install` + `Bootstrap`, ThinkPHP usa `Service`, Hyperf usa `ConfigProvider`, Yii 2 usa `BootstrapInterface` + componente de aplicación e Yii 3 usa `ServiceProviderInterface`, cada uno según las convenciones de su framework.
- **Enlace en el contenedor**: doble vía (nombre de clase + clave de tipo string), idéntica en los seis frameworks: las clases son `HashidsManager`, `HashidsFactory` y `Hashids\Hashids`, y las claves de tipo string son `'hashids'`, `'hashids.factory'` y `'hashids.connection'`; estas dos últimas tienen el mismo nombre que en vinkla/hashids, lo que facilita la migración.
- **Configuración y publicación**: los seis frameworks comparten la misma **forma** de configuración, un **array plano** (con `default` + `connections` en la raíz), y la única diferencia es dónde va: en Laravel / Webman / ThinkPHP es `config/hashids.php`, en Hyperf es `config/autoload/hashids.php`, en Yii 2 es `params['hashids']` dentro de `config/params.php` y en Yii 3 se pasa directamente al constructor de `ServiceProvider` (el framework no impone ningún archivo de configuración).
- **Núcleo sin dependencias de frameworks**: funciona con `new HashidsManager($config, $factory)`, y el parámetro `$config` admite valores que no son arrays (se normalizan a un array vacío).

## Ciclo de vida

![Ciclo de vida](./lifecycle.svg)

| Fase | Qué ocurre |
|------|-----------|
| **Instalación** | `composer require` descarga el paquete → descubrimiento automático en Laravel / el enganche de instalación de Webman copia el esqueleto del plugin / Yii 2 registra el `Bootstrap` o el componente en la configuración de la aplicación / Yii 3 registra el `ServiceProvider` en `ContainerConfig::withProviders()` → se carga `salt` / `length` / `alphabet` |
| **Ejecución** | El contenedor resuelve `HashidsManager` → `encode()` / `decode()` → `__call()` redirige a `connection($name)` → **si acierta en la caché la reutiliza; si no, `HashidsFactory::make()` la construye y la escribe en `$connections`** → devuelve el ID corto o el número original |
| **Desinstalación** | `composer remove` dispara `Install::uninstall()` y elimina `config/plugin/erikwang2013/hashids`; **`config/hashids.php` se conserva**, y es quien use el paquete quien decide si lo borra |

Las conexiones se construyen **bajo demanda**: un proceso que solo usa la conexión por defecto no paga ningún coste de construcción por conexiones que no utiliza, como `alternative`.

## Instalación

```bash
composer require erikwang2013/hashids
```

> **Entorno de ejecución**: el paquete base `hashids/hashids` **necesita** `ext-bcmath` o `ext-gmp` (uno de los dos), o la primera codificación lanzará
> `RuntimeException: Missing math extension for Hashids`. Esas dos extensiones solo figuran en el `suggest` de `hashids/hashids`, y Composer 2 ya no imprime los suggest:
> en una imagen minimalista (como `php:8.3-fpm-alpine`) la instalación no avisa de nada y el fallo aparece en ejecución, con un 500 en cada petición
> que use Hashids. El `suggest` del `composer.json` de este paquete ya incluye esas dos claves, pero aun así debes confirmar que las extensiones están habilitadas.

## Estructura de configuración (Laravel / Webman / ThinkPHP / Yii 2 / Yii 3)

Igual que el `config/hashids.php` del repositorio:

- `default`: nombre de la conexión por defecto (como `main`).
- `connections`: nombre de conexión => `salt`, `length` y, opcionalmente, `alphabet`.

> En **Hyperf** la **ruta** del archivo de configuración es distinta (`config/autoload/hashids.php`), pero la estructura es la misma; consulta la sección de Hyperf más abajo.
>
> **Yii 2** no usa un archivo de configuración propio: coloca el mismo array en `params['hashids']`, dentro del `config/params.php` de la aplicación; consulta la sección de Yii 2 más abajo.
>
> **Yii 3** tampoco lee ningún archivo de configuración: basta con pasar el mismo array al constructor de `ServiceProvider`; consulta la sección de Yii 3 más abajo.

## Uso sin framework

Puedes instanciar el gestor directamente:

```php
use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;

$manager = new HashidsManager(
    require __DIR__ . '/config/hashids.php',
    new HashidsFactory()
);

$hash = $manager->encode(1, 2, 3);
$ids = $manager->decode($hash);
```

---

## Laravel

Similar a [vinkla/hashids](https://github.com/vinkla/laravel-hashids): registra `HashidsManager` en el contenedor, con múltiples conexiones; la conexión por defecto admite Facade y redirección de métodos.

Laravel 5.5+ lee `extra.laravel` del `composer.json` de este paquete y registra automáticamente `HashidsServiceProvider` y el alias de Facade `Hashids`.

**Publicar la configuración (opcional)**

```bash
php artisan vendor:publish --tag=hashids-config
```

Genera `config/hashids.php`. Si no la publicas, el paquete fusiona su configuración por defecto integrada durante la fase de registro.

**Facade (conexión por defecto)**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::encode(1, 2, 3);
$numbers = Hashids::decode($hash);
```

**Indicar una conexión**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::connection('alternative')->encode(100);
```

**Inyección de dependencias de `HashidsManager`**

```php
use Erikwang2013\Hashids\HashidsManager;

public function __construct(private HashidsManager $hashids) {}

$this->hashids->encode(1);
$this->hashids->connection('alternative')->encode(2);
```

**Inyectar el `Hashids\Hashids` subyacente (conexión por defecto)**

```php
use Hashids\Hashids;

public function __construct(private Hashids $hashids) {}
```

Para usar la integración con Laravel, el proyecto debe tener instalado `laravel/framework` (incluido `illuminate/support`, etc.). Este paquete lista `illuminate/*` como **require-dev**, solo para sus propias pruebas.

---

## Webman

Mediante **`Install`** se copian durante la instalación `config/plugin/erikwang2013/hashids` y el **`config/hashids.php`** de la raíz, y el **bootstrap** del plugin registra `HashidsManager` en el contenedor de Webman.

Si el `composer.json` del proyecto ya tiene los enganches `support\Plugin::install` / `update` / `uninstall`, al instalar este paquete se ejecutará automáticamente el script de instalación (`WEBMAN_PLUGIN = true`).

**Archivos tras la instalación**

- `config/plugin/erikwang2013/hashids/app.php`: el interruptor `enable`.
- `config/plugin/erikwang2013/hashids/bootstrap.php`: registra `Erikwang2013\Hashids\Webman\Bootstrap`.
- `config/hashids.php`: configuración multiconexión (se escribe en la primera instalación o cuando se confirma sobrescribir).

Si la copia automática no se ejecutó, puedes copiar a mano la configuración de ejemplo de esas rutas desde el interior del paquete.

**Desactivar el plugin** (`config/plugin/erikwang2013/hashids/app.php`)

```php
<?php
return [
    'enable' => false,
];
```

**Enlace en el contenedor**

- `Erikwang2013\Hashids\HashidsManager` / `'hashids'`
- `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'`
- `Hashids\Hashids` / `'hashids.connection'` (instancia de la conexión por defecto)

**Ejemplo de controlador**

```php
use support\Request;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids;

class DemoController
{
    public function index(Request $request, HashidsManager $manager)
    {
        $hash = $manager->encode(1, 2, 3);

        $client = \support\Container::instance()->get(Hashids::class);
        $hash2 = $client->encode(4);

        return json(['hash' => $hash, 'hash2' => $hash2]);
    }
}
```

**Indicar una conexión**

```php
$manager->connection('alternative')->encode(99);
```

Cuando Composer desinstala el paquete se dispara `Plugin::uninstall`, que elimina `config/plugin/erikwang2013/hashids`; **no** borra `config/hashids.php`, así que conservarlo o no depende de ti.

---

## ThinkPHP

Registra `HashidsManager` mediante una **clase de servicio propia**; la configuración sigue siendo **`config/hashids.php`** con `default` y `connections` en el nivel superior.

**Registrar el servicio**

Añade lo siguiente a `services` en el **`config/service.php`** de la aplicación (la ruta exacta puede variar según la versión de TP):

```php
<?php

return [
    // ...
    \Erikwang2013\Hashids\ThinkPHP\HashidsService::class,
];
```

Si usas el `app/AppService.php` de la aplicación, también puedes escribir un enlace equivalente en `register()`.

**Archivo de configuración**

Copia el `config/hashids.php` del paquete al `config/hashids.php` de la aplicación (o fusiona tú mismo la configuración con el mismo nombre).

```php
<?php
return [
    'default' => 'main',
    'connections' => [
        'main' => [
            'salt' => env('HASHIDS_SALT', ''),
            'length' => (int) env('HASHIDS_LENGTH', 0),
        ],
    ],
];
```

**Ejemplos de uso**

```php
use Erikwang2013\Hashids\HashidsManager;
use think\facade\App;

$manager = App::make(HashidsManager::class);
$hash = $manager->encode(10, 20);
```

```php
$manager = app('hashids');
```

```php
use Hashids\Hashids;

$client = app(Hashids::class);
$hash = $client->encode(1);
```

```php
app(HashidsManager::class)->connection('alternative')->encode(100);
```

La integración con ThinkPHP extiende `think\Service`, así que requiere un entorno con **`topthink/framework`** (este paquete lo lista como suggest).

---

## Hyperf

El **`extra.hyperf.config`** de Composer carga `ConfigProvider`, que registra en el contenedor `HashidsFactory`, `HashidsManager` y el `Hashids\Hashids` de la conexión por defecto.

Copia el **`config/autoload/hashids.php`** del paquete al **`config/autoload/hashids.php`** del proyecto (o usa el comando de publicación de configuración del proyecto).

El archivo debe devolver un **array plano** (con `default` + `connections` en la raíz).

El `ConfigFactory` de Hyperf fusiona por **nombre de archivo** (`Arr::set($config, 'hashids', require $file)`), así que `config('hashids')` devuelve el contenido del archivo tal cual: **no añadas un nivel `'hashids' =>`**. Si lo añades, `connections` deja de ser accesible y al pedir una conexión se lanzará `Hashids connection [main] is not configured`.

```php
<?php

declare(strict_types=1);

return [
    'default' => 'main',
    'connections' => [
        'main' => [
            'salt' => env('HASHIDS_SALT', ''),
            'length' => (int) env('HASHIDS_LENGTH', 0),
        ],
    ],
];
```

**Enlace en el contenedor**

| Abstracción | Implementación |
|------|------|
| `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'` | Construcción por defecto |
| `Erikwang2013\Hashids\HashidsManager` / `'hashids'` | `HashidsManagerFactory` |
| `Hashids\Hashids` / `'hashids.connection'` | `HashidsClientFactory` (conexión por defecto) |

**Inyección en Controller / constructor**

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids;

class DemoController
{
    public function index(HashidsManager $manager, Hashids $hashids)
    {
        $h1 = $manager->encode(1, 2, 3);
        $h2 = $hashids->encode(4);
        $alt = $manager->connection('alternative')->encode(99);

        return compact('h1', 'h2', 'alt');
    }
}
```

```php
$manager = \Hyperf\Context\ApplicationContext::getContainer()->get(
    \Erikwang2013\Hashids\HashidsManager::class
);
```

Los seis frameworks comparten la misma estructura de configuración (`default` + `connections` en la raíz); solo cambia dónde va: Hyperf usa **`config/autoload/hashids.php`**, Laravel / Webman / ThinkPHP usan **`config/hashids.php`**, Yii 2 la coloca en **`params['hashids']` dentro de `config/params.php`** y Yii 3 se la pasa directamente a `ServiceProvider`.

---

## Yii 2

El adaptador implementa **`yii\base\BootstrapInterface`** y registra los seis identificadores en `Yii::$container`; además incluye una subclase de **`yii\base\Component`** para que funcione el `Yii::$app->hashids` habitual de Yii.

### Dos puntos de entrada, elige el que necesites (no son intercambiables)

**Entrada A: el contenedor —— el array `bootstrap` de `config/web.php`**

```php
'bootstrap' => [
    'log',
    \Erikwang2013\Hashids\Yii2\Bootstrap::class,
],
```

```php
use Erikwang2013\Hashids\HashidsManager;

$manager = Yii::$container->get(HashidsManager::class);
$hash    = $manager->encode(10, 20);
$alt     = Yii::$container->get('hashids')->connection('alternative')->encode(100);
```

**Entrada B: componente de aplicación —— el array `components` de `config/web.php`**

```php
'components' => [
    'hashids' => [
        'class'  => \Erikwang2013\Hashids\Yii2\Component::class,
        'config' => [
            'default'     => 'main',
            'connections' => [
                'main' => ['salt' => getenv('HASHIDS_SALT'), 'length' => 6],
            ],
        ],
    ],
],
```

```php
Yii::$app->hashids->encode(1);                              // __call va a la conexión por defecto
Yii::$app->hashids->connection('alternative')->encode(100); // cambio de conexión
Yii::$app->hashids->manager;                                // obtiene el HashidsManager subyacente
```

> **Por qué hacen falta las dos entradas**: `yii\di\ServiceLocator::get()` **no** recurre a `Yii::$container`,
> solo reconoce los componentes registrados en su propio `_definitions`. Así que registrar solo `bootstrap`
> no hace que `Yii::$app->hashids` funcione; y configurar solo `components` tampoco da instancia a `Yii::$container->get()`. Configura la que vayas a usar.

### Archivo de configuración

Yii 2 no tiene una convención tipo `config/hashids.php`: la configuración va en **`params['hashids']`**, dentro del **`config/params.php`** de la aplicación:

```php
return [
    // ...
    'hashids' => [
        'default'     => 'main',
        'connections' => [
            'main' => [
                // Yii 2 no tiene un helper global env() como Laravel, usa getenv()
                // o el mecanismo de env de tu propia aplicación.
                'salt'   => getenv('HASHIDS_SALT') ?: '',
                'length' => (int) (getenv('HASHIDS_LENGTH') ?: 0),
            ],
        ],
    ],
];
```

`Bootstrap` lee exactamente `Yii::$app->params['hashids']`; si la clave no existe se trata como un array vacío
(el nombre de la conexión por defecto recae en `main`) y no lanza error, pero sin salt el hash es enumerable: confirma que el salt está configurado antes de publicar.

> **La conexión por defecto se resuelve de forma perezosa**: en la fase de `bootstrap` solo se registran las
> definiciones; la conexión se construye al obtener realmente un `Hashids\Hashids`. Una configuración mal escrita no impide arrancar la aplicación: lanza `InvalidArgumentException` en el momento de usarla.

La integración con Yii 2 extiende `yii\base\BootstrapInterface` / `yii\base\Component`, así que requiere un
entorno con **`yiisoft/yii2`** (este paquete lo lista como suggest).

---

## Yii 3

Yii 3 es un conjunto de paquetes ensamblados sobre un contenedor PSR-11 (`yiisoft/di`), sin el array de
configuración de aplicación de Yii 2. El adaptador es una clase que implementa **`Yiisoft\Di\ServiceProviderInterface`** y cuyo `getDefinitions()` devuelve seis identificadores de contenedor.

**Registro**

```php
use Erikwang2013\Hashids\Yii3\ServiceProvider;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withProviders([
        // La configuración se pasa directamente al provider —— Yii 3 no impone
        // una ubicación de archivo: de dónde salen los valores (params, env) lo decide la aplicación.
        new ServiceProvider($params['hashids'] ?? []),
    ]);

$container = new Container($config);
```

**Uso**

```php
use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids;

$manager = $container->get(HashidsManager::class);
$hash    = $manager->encode(10, 20);                                  // conexión por defecto

$container->get('hashids')->connection('alternative')->encode(100);   // alias de tipo string + cambio de conexión
$container->get(Hashids::class)->encode(1);                           // conexión por defecto por nombre de clase
$container->get(HashidsFactory::class);                               // fábrica
```

El contenedor ya tiene semántica de singleton (el mismo id devuelve siempre la misma instancia en cada `get()`),
así que el alias y el FQCN obtienen las mismas conexiones; la definición del adaptador solo tiene que reenviar, sin crear objetos de antemano.

> **La clase del error lleva una capa más**: la conexión por defecto se resuelve de forma perezosa, así que la
> fase de ensamblado no falla; pero al usarla, el contenedor envuelve el `InvalidArgumentException` de este
> paquete en una **`Yiisoft\Di\BuildingException`**: poner `InvalidArgumentException` en el `catch` no lo captura, hay que mirar `$e->getPrevious()`.

> **Las definiciones del provider tienen prioridad sobre las definitions normales**: el contenedor real ensambla primero
> `definitions` y después `providers`, así que para sobrescribir el enlace de hashids cambiar la clave del mismo nombre en `definitions` **no sirve**: hay que usar `extensions` o modificar el provider.

La integración con Yii 3 implementa `Yiisoft\Di\ServiceProviderInterface`, así que requiere un entorno con
**`yiisoft/di`** (este paquete lo lista como suggest).

---

## La mascota del proyecto

La mascota del proyecto es **Hashy**: una pequeña criatura cuadrada de esquinas redondeadas, con una antena en la cabeza y un `#` grabado en el pecho. La imagen vectorial está en [`docs/mascot.svg`](../../mascot.svg).

En el terminal no hay SVG, así que el código incluye una versión ASCII equivalente (`Erikwang2013\Hashids\Mascot`):

```php
use Erikwang2013\Hashids\Mascot;

echo Mascot::greet();
```

```
           ●
           │
      ╭─────────╮
      │ ◉     ◉ │
      │    ‿    │
      │    #    │
      ╰──┬───┬──╯
         ╵   ╵
哈希迪 Hashy · 把数据库自增 ID 换成短小、不可猜测的字符串
```

La primera vez que el plugin de Webman publica `config/hashids.php` imprime este saludo de paso; si la configuración ya existe (por ejemplo, al repetir `composer update`) no vuelve a molestar.

---

## El código abierto no es fácil, tu apoyo es bienvenido

<p align="center">
  <img src="../../weixinpay.png" alt="WeChat Pay" width="130" height="130" />
  &nbsp;&nbsp;&nbsp;&nbsp;
  <img src="../../alipay.png" alt="Alipay" width="130" height="130" />
</p>

<p align="center"><strong>WeChat Pay</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Alipay</strong></p>

---


## Licencia

MIT. Consulta [LICENSE](../../../LICENSE).
