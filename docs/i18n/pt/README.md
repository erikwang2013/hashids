# erikwang2013/hashids

**Languages:** [中文](../../../README.md) · [English](../en/README.md) · [한국어](../ko/README.md) · [Русский](../ru/README.md) · [Deutsch](../de/README.md) · [Français](../fr/README.md) · [Español](../es/README.md) · **Português** · [हिन्दी](../hi/README.md) · [العربية](../ar/README.md) · [বাংলা](../bn/README.md) · [Bahasa Indonesia](../id/README.md) · [日本語](../ja/README.md)

<p align="center">
  <img src="../../mascot.svg" alt="Hashy — mascote do projeto erikwang2013/hashids" width="200" />
</p>

<p align="center"><strong>Hashy</strong> — o mascote do projeto, o <code>#</code> no peito é a sua marca</p>

Troque os IDs auto-incrementais do banco por strings curtas e impossíveis de adivinhar, **com uma única API rodando em Laravel, Webman, ThinkPHP, Hyperf, Yii 2 e Yii 3 ao mesmo tempo**.

Baseado em [`hashids/hashids`](https://github.com/vinkla/hashids) v5; a configuração e o uso seguem [**vinkla/hashids**](https://github.com/vinkla/laravel-hashids) (múltiplas conexões, conexão padrão, `HashidsManager` + factory), permitindo substituição direta.

## Sobre o projeto

**Hashids** é um gerador de IDs curtos: codifica IDs numéricos (como chaves primárias do banco) em strings curtas, únicas e impossíveis de adivinhar. Diferente de UUID ou Snowflake ID — Hashids é mais indicado para cenários voltados ao usuário (URLs, códigos de compartilhamento, número de pedido etc.), ocultando o número original sem abrir mão de um resultado curto e legível.

Este pacote `erikwang2013/hashids` é a **camada de integração multi-framework em PHP** do Hashids, com design inspirado e alinhado ao estilo de API do [vinkla/hashids](https://github.com/vinkla/laravel-hashids) (múltiplas conexões, conexão padrão, padrão Manager + Factory), e estende o suporte a outros frameworks PHP populares na China.

**Principais recursos:**

- **Compatibilidade multi-framework**: a mesma API atende Laravel, Webman, ThinkPHP, Hyperf, Yii 2 e Yii 3, com custo de migração baixíssimo.
- **Múltiplas conexões**: um aplicativo pode configurar vários pares Salt/Length (por exemplo, salts diferentes para IDs de usuário e de pedido), alternando com `connection('xxx')`.
- **Sem dependência de framework**: funciona de forma independente, basta `new HashidsManager($config, $factory)`.
- **Alinhado ao vinkla/hashids**: no Laravel, a Facade, o binding no container e o formato de `config/hashids.php` são iguais aos do vinkla/hashids, permitindo substituição direta.
- **Estilo nativo de cada framework**: cada integração segue as convenções do respectivo framework — Laravel com ServiceProvider + Facade, Webman com Plugin + Bootstrap, ThinkPHP com Service, Hyperf com ConfigProvider, Yii 2 com Bootstrap + componente de aplicação, Yii 3 com ServiceProvider.

**Cenários de uso:**

| Cenário | Descrição |
|------|------|
| Ocultar IDs auto-incrementais do banco | Mapeia `user_id=100` para `/user/3kTMd`, sem expor o tamanho da operação |
| Gerar links curtos / códigos de compartilhamento | Mais curto que UUID e mais controlável que uma string aleatória |
| Número de pedido / protocolo | Boa legibilidade, facilita o atendimento e a análise de logs |
| Isolamento multi-tenant / multi-módulo | Conexões diferentes usam salts diferentes, mantendo os espaços de codificação independentes |

**Atenção:**

- Hashids é **codificação (encode/decode), não criptografia**. O salt apenas dificulta a adivinhação e não serve para cenários sensíveis de segurança (como token e senha).
- Depois de entrar em produção, alterar o Salt ou o Length invalida todos os IDs já codificados; planeje e fixe a configuração com antecedência.
- **Salt vazio equivale a nenhuma proteção**: com salt vazio o resultado da codificação é enumerável (`encode(1)`, `encode(2)`… em ordem previsível). Escrito como
  `env('HASHIDS_SALT', '')`, esquecer a variável de ambiente não gera erro nem aviso, só um rebaixamento silencioso para salt vazio — antes de ir para produção, confirme que o salt está definido.
- **Em frameworks residentes em memória (Webman / Hyperf), mudar a configuração exige reiniciar o processo**: o `HashidsManager` tira um snapshot da configuração no construtor e cacheia as conexões para sempre,
  então alterar `config/hashids.php` (ou trocar o salt por uma central de configuração) não entra em vigor a quente, só depois de `reload` ou de reiniciar.

## Estrutura do projeto

```
hashids/
├── src/
│   ├── HashidsManager.php                     # Núcleo: resolução multi-conexão, cache de instâncias, proxy da conexão padrão
│   ├── HashidsFactory.php                     # Núcleo: cria a instância Hashids a partir da config da conexão
│   ├── Mascot.php                             # Versão ASCII do mascote do projeto, o Hashy
│   ├── Install.php                            # Hooks de instalação / desinstalação do Webman
│   ├── Laravel/
│   │   ├── HashidsServiceProvider.php         # Singleton no container + alias + publish da config
│   │   └── Facades/Hashids.php                # Facade (conexão padrão)
│   ├── Webman/
│   │   └── Bootstrap.php                      # Registra as definições no container na inicialização do processo
│   ├── ThinkPHP/
│   │   └── HashidsService.php                 # Registro de bindings do think\Service
│   ├── Hyperf/
│   │   ├── ConfigProvider.php                 # Mapeamento de dependências + publish da config
│   │   ├── HashidsManagerFactory.php
│   │   └── HashidsClientFactory.php
│   ├── Yii2/
│   │   ├── Bootstrap.php                      # BootstrapInterface: registra Yii::$container
│   │   └── Component.php                      # Componente de aplicação: Yii::$app->hashids
│   ├── Yii3/
│   │   └── ServiceProvider.php                # ServiceProviderInterface: retorna seis definições
│   └── config/plugin/erikwang2013/hashids/    # Esqueleto do plugin Webman (copiado para o projeto na instalação)
│       ├── app.php                            # Chave enable
│       └── bootstrap.php                      # Registra Webman\Bootstrap
├── config/
│   ├── hashids.php                            # Config plana: Laravel / Webman / ThinkPHP
│   └── autoload/hashids.php                   # Config do Hyperf (mesma estrutura, outro caminho)
├── tests/
│   ├── HashidsManagerTest.php                 # Comportamento do núcleo
│   ├── HashidsFactoryTest.php
│   ├── InstallTest.php
│   ├── MascotTest.php                         # Mascote do projeto: alinhamento dos glifos e saudação
│   ├── Laravel/ · Webman/ · ThinkPHP/ · Hyperf/ · Yii2/ · Yii3/   # Testes de adaptação de cada framework
│   ├── Config/PluginConfigTest.php
│   ├── Contract/                              # Testes de contrato com frameworks reais (job separado na CI, veja abaixo)
│   └── Support/FrameworkStubs.php             # Dublês das classes de framework usados nos testes
├── docs/
│   ├── mascot.svg                             # Mascote do projeto, o Hashy
│   ├── architecture.svg                       # Diagrama de arquitetura
│   ├── features.svg                           # Diagrama de design funcional
│   └── lifecycle.svg                          # Diagrama de ciclo de vida
├── .github/workflows/release.yml              # Após push na main, cria a tag e publica o release automaticamente
├── composer.json
└── phpunit.xml.dist
```

> Artefatos de dependência como `vendor/` e `composer.lock` não são listados; `src/config/` é o **esqueleto do plugin** e `config/` é o **exemplo de configuração publicável** — os dois têm finalidades diferentes.

## Arquitetura

![Arquitetura](./architecture.svg)

**Quatro camadas com dependência unidirecional**: a camada de cima depende da de baixo, e nunca o contrário:

| Camada | Responsabilidade | Local |
|----|------|------|
| Camada de chamada da aplicação | Três entradas: Facade, container / função auxiliar, injeção por construtor | Código da aplicação |
| Camada de adaptação | Só faz a ligação: binding no container, leitura de config, publish da config | `src/<Framework>/` |
| Camada core | Gestão de múltiplas conexões e construção de instâncias, **sem depender de framework algum** | `src/HashidsManager.php`, `src/HashidsFactory.php` |
| Dependência de base | Implementação real de encode/decode | `hashids/hashids` |

A camada core é o centro de gravidade do pacote: `HashidsManager` guarda a configuração, `connection($name)` constrói e cacheia conexões sob demanda, e `__call()` encaminha para a conexão padrão as chamadas de método sem conexão explícita; `HashidsFactory::make()` é o único lugar que constrói um `Hashids\Hashids`. As classes de adaptação dos seis frameworks somam cerca de 490 linhas (incluindo a Facade, as duas factories do Hyperf e o componente de aplicação do Yii 2) — elas só ligam o núcleo ao container de cada um.

## Design funcional

![Design funcional](./features.svg)

Seis grupos de capacidades, todos em torno do mesmo núcleo:

- **API de codificação**: `encode()` / `decode()` / `encodeHex()` / `decodeHex()`, que via `__call()` chegam à conexão padrão, sem precisar de `connection()` explícito.
- **Gestão de múltiplas conexões**: `connection('alternative')` faz a troca; construção lazy, cada conexão é construída uma única vez.
- **Adaptação multi-framework**: Laravel usa `ServiceProvider`, Webman usa `Install` + `Bootstrap`, ThinkPHP usa `Service`, Hyperf usa `ConfigProvider`, Yii 2 usa `BootstrapInterface` + componente de aplicação, Yii 3 usa `ServiceProviderInterface`, cada um seguindo as convenções do framework.
- **Binding no container**: binding em duas vias, por **nome de classe** (`HashidsManager`, `HashidsFactory`, `Hashids\Hashids`) e por **chave string** (`'hashids'`, `'hashids.factory'`, `'hashids.connection'`), igual nos seis frameworks; as duas últimas chaves têm os mesmos nomes das do vinkla/hashids, o que facilita a migração.
- **Configuração e publicação**: os seis frameworks têm a **mesma forma** de configuração, um **array plano** (com `default` + `connections` na raiz); a diferença está só em onde ela fica — Laravel / Webman / ThinkPHP usam `config/hashids.php`, Hyperf usa `config/autoload/hashids.php`, Yii 2 fica em `params['hashids']` dentro do `config/params.php` e o Yii 3 recebe o array direto no construtor do `ServiceProvider` (o framework não define arquivo de configuração).
- **Núcleo sem dependência de framework**: `new HashidsManager($config, $factory)` já funciona, e o parâmetro `$config` tolera valores que não são array (normalizados para array vazio).

## Ciclo de vida

![Ciclo de vida](./lifecycle.svg)

| Fase | O que acontece |
|------|-----------|
| **Instalação** | `composer require` baixa o pacote → auto-discovery do Laravel / hook de instalação do Webman copia o esqueleto do plugin / Yii 2 registra o `Bootstrap` ou o componente na configuração da aplicação / Yii 3 registra o `ServiceProvider` em `ContainerConfig::withProviders()` → carrega `salt` / `length` / `alphabet` |
| **Execução** | O container resolve o `HashidsManager` → `encode()` / `decode()` → `__call()` encaminha para `connection($name)` → **em caso de hit, reutiliza o cache; só em caso de miss o `HashidsFactory::make()` constrói e grava em `$connections`** → retorna o ID curto ou o número original |
| **Desinstalação** | `composer remove` dispara `Install::uninstall()`, removendo `config/plugin/erikwang2013/hashids`; **`config/hashids.php` é preservado**, e a decisão de limpar fica com o usuário |

A conexão é **construída sob demanda**: processos que só usam a conexão padrão não pagam nenhum custo de construção por conexões não utilizadas, como `alternative`.

## Instalação

```bash
composer require erikwang2013/hashids
```

> **Ambiente de execução**: o `hashids/hashids` de baixo nível **exige** `ext-bcmath` ou `ext-gmp` (um dos dois), senão a primeira codificação lança
> `RuntimeException: Missing math extension for Hashids`. Essas duas extensões constam apenas em `suggest` no `hashids/hashids`, e o Composer 2 não imprime mais o suggest —
> por isso, em imagens enxutas (como `php:8.3-fpm-alpine`), não há aviso algum na instalação e a falha só aparece em execução,
> com 500 em toda requisição que use Hashids. O `suggest` do `composer.json` deste pacote já traz as duas chaves, mas ainda é você quem confirma que a extensão está habilitada.

## Estrutura de configuração (Laravel / Webman / ThinkPHP / Yii 2 / Yii 3)

Igual ao `config/hashids.php` do repositório:

- `default`: nome da conexão padrão (por exemplo, `main`).
- `connections`: nome da conexão => `salt`, `length` e `alphabet` opcional.

> O **Hyperf** tem um caminho de arquivo de configuração diferente (`config/autoload/hashids.php`), mas a estrutura é a mesma; veja a seção do Hyperf abaixo.
>
> O **Yii 2** não usa arquivo de configuração próprio: coloque o mesmo array em `params['hashids']` no `config/params.php` da aplicação; veja a seção do Yii 2 abaixo.
>
> O **Yii 3** também não lê arquivo de configuração: basta passar o mesmo array para o construtor do `ServiceProvider`; veja a seção do Yii 3 abaixo.

## Uso sem framework

É possível instanciar o manager diretamente:

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

Semelhante ao [vinkla/hashids](https://github.com/vinkla/laravel-hashids): registra o `HashidsManager` no container, com múltiplas conexões; a conexão padrão suporta Facade e encaminhamento de métodos.

O Laravel 5.5+ lê o `extra.laravel` do `composer.json` deste pacote e registra automaticamente o `HashidsServiceProvider` e o alias de Facade `Hashids`.

**Publicar a configuração (opcional)**

```bash
php artisan vendor:publish --tag=hashids-config
```

Gera `config/hashids.php`. Se não publicar, o pacote mescla a configuração padrão embutida durante o registro.

**Facade (conexão padrão)**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::encode(1, 2, 3);
$numbers = Hashids::decode($hash);
```

**Conexão específica**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::connection('alternative')->encode(100);
```

**Injeção de dependência do `HashidsManager`**

```php
use Erikwang2013\Hashids\HashidsManager;

public function __construct(private HashidsManager $hashids) {}

$this->hashids->encode(1);
$this->hashids->connection('alternative')->encode(2);
```

**Injetar o `Hashids\Hashids` de baixo nível (conexão padrão)**

```php
use Hashids\Hashids;

public function __construct(private Hashids $hashids) {}
```

Para rodar a integração com o Laravel, o projeto precisa ter o `laravel/framework` instalado (incluindo `illuminate/support` etc.). Este pacote lista `illuminate/*` como **require-dev**, apenas para os testes do próprio pacote.

---

## Webman

No momento da instalação, o **`Install`** copia `config/plugin/erikwang2013/hashids` e o **`config/hashids.php`** da raiz, e o **bootstrap** do plugin registra o `HashidsManager` no container do Webman.

Se o `composer.json` do projeto já tiver os hooks `support\Plugin::install` / `update` / `uninstall`, o script de instalação deste pacote é executado automaticamente (`WEBMAN_PLUGIN = true`).

**Arquivos após a instalação**

- `config/plugin/erikwang2013/hashids/app.php`: a chave `enable`.
- `config/plugin/erikwang2013/hashids/bootstrap.php`: registra `Erikwang2013\Hashids\Webman\Bootstrap`.
- `config/hashids.php`: configuração multi-conexão (gravada na primeira instalação ou quando você confirma a sobrescrita).

Se a cópia automática não for executada, copie manualmente do pacote a configuração de exemplo nos caminhos acima.

**Desligar o plugin** (`config/plugin/erikwang2013/hashids/app.php`)

```php
<?php
return [
    'enable' => false,
];
```

**Binding no container**

- `Erikwang2013\Hashids\HashidsManager` / `'hashids'`
- `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'`
- `Hashids\Hashids` / `'hashids.connection'` (instância da conexão padrão)

**Exemplo de controller**

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

**Conexão específica**

```php
$manager->connection('alternative')->encode(99);
```

Ao desinstalar o pacote, o Composer dispara `Plugin::uninstall` e remove `config/plugin/erikwang2013/hashids`; **não** apaga `config/hashids.php`, e manter ou não fica a seu critério.

---

## ThinkPHP

Registra o `HashidsManager` por meio de uma **classe de serviço personalizada**; a configuração continua sendo o **`config/hashids.php`** com `default` e `connections` no nível raiz.

**Registrar o serviço**

No `services` do **`config/service.php`** da aplicação (o caminho exato pode variar conforme a versão do TP), adicione:

```php
<?php

return [
    // ...
    \Erikwang2013\Hashids\ThinkPHP\HashidsService::class,
];
```

Se você usa o `app/AppService.php` no nível da aplicação, também pode escrever o binding equivalente em `register()`.

**Arquivo de configuração**

Copie o `config/hashids.php` do pacote para o `config/hashids.php` da aplicação (ou mescle você mesmo as configurações de mesmo nome).

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

**Exemplos de uso**

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

A integração com o ThinkPHP estende `think\Service` e exige um ambiente com **`topthink/framework`** (este pacote o lista como suggest).

---

## Hyperf

O **`extra.hyperf.config`** do Composer carrega o `ConfigProvider`, que registra no container o `HashidsFactory`, o `HashidsManager` e o `Hashids\Hashids` da conexão padrão.

Copie o **`config/autoload/hashids.php`** do pacote para o **`config/autoload/hashids.php`** do projeto (ou use o comando de publish de configuração do projeto).

Esse arquivo deve retornar um **array plano** (com `default` + `connections` na raiz).

O `ConfigFactory` do Hyperf mescla pelo **nome do arquivo** (`Arr::set($config, 'hashids', require $file)`), então `config('hashids')` devolve o próprio conteúdo do arquivo — **não envolva em mais uma camada `'hashids' =>`**. Se envolver, `connections` fica inacessível e a obtenção da conexão lança `Hashids connection [main] is not configured`.

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

**Binding no container**

| Abstração | Implementação |
|------|------|
| `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'` | Construtor padrão |
| `Erikwang2013\Hashids\HashidsManager` / `'hashids'` | `HashidsManagerFactory` |
| `Hashids\Hashids` / `'hashids.connection'` | `HashidsClientFactory` (conexão padrão) |

**Controller / injeção por construtor**

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

Os seis frameworks usam a **mesma estrutura** de configuração (com `default` + `connections` na raiz); a diferença está só em onde ela fica: o Hyperf usa **`config/autoload/hashids.php`**, Laravel / Webman / ThinkPHP usam **`config/hashids.php`**, o Yii 2 fica em **`params['hashids']` do `config/params.php`** e o Yii 3 recebe o array direto no `ServiceProvider`.

---

## Yii 2

O adaptador implementa **`yii\base\BootstrapInterface`** e registra os seis identificadores em `Yii::$container`; além disso oferece uma subclasse de **`yii\base\Component`**, que deixa disponível o `Yii::$app->hashids` idiomático do Yii.

### Dois pontos de entrada, escolha conforme a necessidade (um não substitui o outro)

**Entrada A: container — o array `bootstrap` do `config/web.php`**

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

**Entrada B: componente de aplicação — o array `components` do `config/web.php`**

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
Yii::$app->hashids->encode(1);                              // __call encaminha para a conexão padrão
Yii::$app->hashids->connection('alternative')->encode(100); // troca de conexão
Yii::$app->hashids->manager;                                // obtém o HashidsManager subjacente
```

> **Por que os dois pontos de entrada são necessários**: `yii\di\ServiceLocator::get()` **não** recorre ao `Yii::$container`,
> só reconhece os componentes registrados nas próprias `_definitions`. Por isso, registrar apenas o `bootstrap` não faz o
> `Yii::$app->hashids` funcionar; e configurar só `components` também não faz o `Yii::$container->get()` receber a instância. Configure o que você for usar.

### Arquivo de configuração

O Yii 2 não tem essa convenção de `config/hashids.php`; a configuração fica em `params['hashids']` no **`config/params.php`** da aplicação:

```php
return [
    // ...
    'hashids' => [
        'default'     => 'main',
        'connections' => [
            'main' => [
                // O Yii 2 não tem um helper global env() como o do Laravel; use getenv()
                // ou o mecanismo de env da sua própria aplicação.
                'salt'   => getenv('HASHIDS_SALT') ?: '',
                'length' => (int) (getenv('HASHIDS_LENGTH') ?: 0),
            ],
        ],
    ],
];
```

É esse `Yii::$app->params['hashids']` que o `Bootstrap` lê; quando a chave não existe, ela é tratada como array vazio (e o nome da conexão padrão volta a ser
`main`), sem erro — mas sem salt o hash é enumerável, então confirme que o salt está definido antes de ir para produção.

> **A conexão padrão é resolvida de forma lazy**: a fase de `bootstrap` apenas registra as definições; a conexão só é construída quando o `Hashids\Hashids` é realmente obtido.
> Uma configuração errada não faz a aplicação inteira falhar ao subir, mas lança `InvalidArgumentException` no momento do uso.

A integração com o Yii 2 estende `yii\base\BootstrapInterface` / `yii\base\Component` e exige um ambiente com **`yiisoft/yii2`**
(este pacote o lista como suggest).

---

## Yii 3

O Yii 3 é um conjunto de pacotes montados sobre um container PSR-11 (`yiisoft/di`), sem aquele array de configuração de aplicação do Yii 2.
O adaptador é uma classe que implementa **`Yiisoft\Di\ServiceProviderInterface`**, cujo `getDefinitions()` retorna seis identificadores de container.

**Registro**

```php
use Erikwang2013\Hashids\Yii3\ServiceProvider;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withProviders([
        // A configuração vai direto para o provider —— o Yii 3 não define o local do
        // arquivo; de onde vêm os valores (params, env, central de config) fica a critério da aplicação.
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
$hash    = $manager->encode(10, 20);                                  // conexão padrão

$container->get('hashids')->connection('alternative')->encode(100);   // alias de string + troca de conexão
$container->get(Hashids::class)->encode(1);                           // conexão padrão pelo nome da classe
$container->get(HashidsFactory::class);                               // factory
```

O container já tem semântica de singleton (o mesmo id devolve a mesma instância a cada `get()`), então o alias e o FQCN recebem o mesmo conjunto de conexões,
e as definições do adaptador só precisam encaminhar, sem construir objetos com antecedência.

> **A classe do erro vem embrulhada em uma camada extra**: a conexão padrão é resolvida de forma lazy, então a fase de montagem não falha; mas, no uso, o container embrulha o
> `InvalidArgumentException` deste pacote em **`Yiisoft\Di\BuildingException`** e o lança —— um `catch` de
> `InvalidArgumentException` não pega, é preciso olhar `$e->getPrevious()`.

> **As definições do provider têm prioridade sobre as definitions comuns**: o container real monta primeiro as `definitions` e depois os `providers`,
> então, para sobrescrever o binding do hashids, alterar a chave de mesmo nome em `definitions` **não funciona**; use `extensions` ou altere o provider.

A integração com o Yii 3 implementa `Yiisoft\Di\ServiceProviderInterface` e exige um ambiente com **`yiisoft/di`**
(este pacote o lista como suggest).

---

## Mascote do projeto

O mascote do projeto é o **Hashy** — um blocão de cantos arredondados, com antena na cabeça e um `#` estampado no peito; o vetor está em [`docs/mascot.svg`](../../mascot.svg).

Como o terminal não tem SVG, o código traz uma versão ASCII equivalente (`Erikwang2013\Hashids\Mascot`):

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

Na primeira vez que o plugin do Webman publica o `config/hashids.php`, essa saudação é impressa uma vez; se a configuração já existir (por exemplo, ao repetir um `composer update`), você não será incomodado de novo.

---

## Código aberto não é fácil, seu apoio é bem-vindo

<p align="center">
  <img src="../../weixinpay.png" alt="WeChat Pay" width="130" height="130" />
  &nbsp;&nbsp;&nbsp;&nbsp;
  <img src="../../alipay.png" alt="Alipay" width="130" height="130" />
</p>

<p align="center"><strong>WeChat Pay</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Alipay</strong></p>

---


## Licença

MIT. Veja [LICENSE](../../../LICENSE).
