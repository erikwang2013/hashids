# erikwang2013/hashids

**Languages:** [中文](../../../README.md) · [English](../en/README.md) · [한국어](../ko/README.md) · [Русский](../ru/README.md) · [Deutsch](../de/README.md) · [Français](../fr/README.md) · [Español](../es/README.md) · [Português](../pt/README.md) · **हिन्दी** · [العربية](../ar/README.md) · [বাংলা](../bn/README.md) · [Bahasa Indonesia](../id/README.md) · [日本語](../ja/README.md)

<p align="center">
  <img src="../../mascot.svg" alt="हैशी Hashy — erikwang2013/hashids प्रोजेक्ट मस्कॉट" width="200" />
</p>

<p align="center"><strong>हैशी Hashy</strong> — प्रोजेक्ट मस्कॉट, सीने पर बना <code>#</code> इसकी पहचान है</p>

डेटाबेस के ऑटो-इंक्रीमेंट ID को छोटे, अनुमान-रहित स्ट्रिंग में बदलें — **एक ही API Laravel, Webman, ThinkPHP, Hyperf, Yii 2 और Yii 3 पर एक साथ चलती है**।

अंदर से [`hashids/hashids`](https://github.com/vinkla/hashids) v5 पर निर्भर; कॉन्फ़िगरेशन और उपयोग [**vinkla/hashids**](https://github.com/vinkla/laravel-hashids) के अनुरूप (मल्टी-कनेक्शन, डिफ़ॉल्ट कनेक्शन, `HashidsManager` + फ़ैक्टरी), इसलिए सहज प्रतिस्थापन संभव है।

## परियोजना परिचय

**Hashids** एक शॉर्ट ID जनरेटर है, जो संख्यात्मक ID (जैसे डेटाबेस प्राइमरी की) को छोटे, अद्वितीय और अनुमान-रहित स्ट्रिंग में encode करता है। यह UUID या Snowflake ID से अलग है — Hashids उपयोगकर्ता-मुखी परिदृश्यों (URL, शेयर कोड, ऑर्डर नंबर आदि) के लिए अधिक उपयुक्त है, जहाँ छोटा और पठनीय बनाए रखते हुए मूल संख्या छिपाई जाती है।

यह पैकेज `erikwang2013/hashids` Hashids के लिए एक **PHP मल्टी-फ्रेमवर्क इंटीग्रेशन लेयर** है। इसका डिज़ाइन [vinkla/hashids](https://github.com/vinkla/laravel-hashids) की API शैली (मल्टी-कनेक्शन, डिफ़ॉल्ट कनेक्शन, Manager + Factory पैटर्न) को संदर्भित करता है और उससे मेल खाता है, तथा इसमें अन्य प्रचलित PHP फ्रेमवर्क के लिए समर्थन भी जोड़ा गया है।

**मुख्य विशेषताएँ:**

- **मल्टी-फ्रेमवर्क संगतता**: एक ही API Laravel, Webman, ThinkPHP, Hyperf, Yii 2 और Yii 3 पर एक साथ चलती है, माइग्रेशन लागत न्यूनतम।
- **मल्टी-कनेक्शन समर्थन**: एक ही ऐप में Salt/Length के कई सेट एक साथ कॉन्फ़िगर किए जा सकते हैं (जैसे यूज़र ID और ऑर्डर ID के लिए अलग salt), और `connection('xxx')` से स्विच किया जा सकता है।
- **फ्रेमवर्क-निरपेक्ष**: किसी विशेष फ्रेमवर्क पर निर्भरता के बिना स्वतंत्र रूप से उपयोग किया जा सकता है; सीधे `new HashidsManager($config, $factory)` से काम चलता है।
- **vinkla/hashids के अनुरूप**: Laravel में Facade, कंटेनर बाइंडिंग और `config/hashids.php` का प्रारूप vinkla/hashids के समान है, इसलिए सहज प्रतिस्थापन संभव है।
- **फ्रेमवर्क-मूल शैली**: हर फ्रेमवर्क इंटीग्रेशन अपनी परंपरागत शैली का पालन करता है — Laravel में ServiceProvider + Facade, Webman में Plugin + Bootstrap, ThinkPHP में Service, Hyperf में ConfigProvider, Yii 2 में Bootstrap + ऐप कंपोनेंट, Yii 3 में ServiceProvider।

**उपयुक्त परिदृश्य:**

| परिदृश्य | विवरण |
|------|------|
| डेटाबेस ऑटो-इंक्रीमेंट ID छिपाना | `user_id=100` को `/user/3kTMd` में मैप करें, जिससे बिज़नेस का आकार उजागर न हो |
| शॉर्ट लिंक / शेयर कोड बनाना | UUID से छोटा, रैंडम स्ट्रिंग से अधिक नियंत्रित |
| ऑर्डर नंबर / ट्रांज़ैक्शन नंबर | पठनीयता अच्छी, सपोर्ट टीम और लॉग जाँच में सुविधाजनक |
| मल्टी-टेनेंट / मल्टी-मॉड्यूल आइसोलेशन | अलग-अलग कनेक्शन में अलग Salt, जिससे encode स्पेस एक-दूसरे से स्वतंत्र रहे |

**ध्यान दें:**

- Hashids **encode/decode है, एन्क्रिप्शन नहीं**। Salt केवल अनुमान लगाना कठिन बनाता है; इसे सुरक्षा-संवेदनशील परिदृश्यों (जैसे token, पासवर्ड) में उपयोग न करें।
- लाइव जाने के बाद Salt या Length बदलने पर पहले से encode किए गए सभी ID अमान्य हो जाएँगे; कृपया पहले से योजना बनाएँ और कॉन्फ़िगरेशन स्थिर रखें।
- **Salt खाली छोड़ने का मतलब कोई सुरक्षा नहीं**: खाली salt पर encode का नतीजा अनुमान लगाकर निकाला जा सकता है (`encode(1)`, `encode(2)`… क्रम पहले से अनुमानित)।
  `env('HASHIDS_SALT', '')` लिखने पर अगर एनवायरनमेंट वेरिएबल सेट न हो, तो न कोई एरर आता है न कोई चेतावनी — बस चुपचाप salt खाली हो जाता है; लाइव करने से पहले पुष्टि करें कि salt सेट है।
- **मेमोरी में रहने वाले फ्रेमवर्क (Webman / Hyperf) में कॉन्फ़िग बदलने पर प्रोसेस रीस्टार्ट ज़रूरी है**: `HashidsManager` बनते समय कॉन्फ़िग का स्नैपशॉट ले लेता है और कनेक्शन हमेशा के लिए कैश कर देता है,
  इसलिए `config/hashids.php` बदलने (या कॉन्फ़िग सेंटर से salt बदलने) पर वह हॉट लागू नहीं होगा — `reload` या रीस्टार्ट के बाद ही।

## परियोजना संरचना

```
hashids/
├── src/
│   ├── HashidsManager.php                     # कोर: मल्टी-कनेक्शन रिज़ॉल्यूशन, इंस्टेंस कैश, डिफ़ॉल्ट कनेक्शन प्रॉक्सी
│   ├── HashidsFactory.php                     # कोर: कनेक्शन कॉन्फ़िग से Hashids इंस्टेंस बनाना
│   ├── Mascot.php                             # प्रोजेक्ट मस्कॉट "हैशी Hashy" का ASCII संस्करण
│   ├── Install.php                            # Webman इंस्टॉल / अनइंस्टॉल हुक
│   ├── Laravel/
│   │   ├── HashidsServiceProvider.php         # कंटेनर सिंगलटन + उपनाम + कॉन्फ़िग पब्लिश
│   │   └── Facades/Hashids.php                # Facade (डिफ़ॉल्ट कनेक्शन)
│   ├── Webman/
│   │   └── Bootstrap.php                      # प्रोसेस स्टार्ट पर कंटेनर डेफ़िनिशन रजिस्टर करना
│   ├── ThinkPHP/
│   │   └── HashidsService.php                 # think\Service पंजीकरण बाइंडिंग
│   ├── Hyperf/
│   │   ├── ConfigProvider.php                 # डिपेंडेंसी मैपिंग + कॉन्फ़िग पब्लिश
│   │   ├── HashidsManagerFactory.php
│   │   └── HashidsClientFactory.php
│   ├── Yii2/
│   │   ├── Bootstrap.php                      # BootstrapInterface: Yii::$container रजिस्टर करना
│   │   └── Component.php                      # ऐप कंपोनेंट: Yii::$app->hashids
│   ├── Yii3/
│   │   └── ServiceProvider.php                # ServiceProviderInterface: छह डेफ़िनिशन लौटाना
│   └── config/plugin/erikwang2013/hashids/    # Webman प्लगइन स्केलेटन (इंस्टॉल पर प्रोजेक्ट में कॉपी होता है)
│       ├── app.php                            # enable स्विच
│       └── bootstrap.php                      # Webman\Bootstrap रजिस्टर करना
├── config/
│   ├── hashids.php                            # फ्लैट कॉन्फ़िग: Laravel / Webman / ThinkPHP
│   └── autoload/hashids.php                   # Hyperf कॉन्फ़िग (संरचना समान, पथ अलग)
├── tests/
│   ├── HashidsManagerTest.php                 # कोर व्यवहार
│   ├── HashidsFactoryTest.php
│   ├── InstallTest.php
│   ├── MascotTest.php                         # प्रोजेक्ट मस्कॉट: ग्लिफ़ संरेखण और अभिवादन
│   ├── Laravel/ · Webman/ · ThinkPHP/ · Hyperf/ · Yii2/ · Yii3/   # विभिन्न फ्रेमवर्क के अडैप्टर टेस्ट
│   ├── Config/PluginConfigTest.php
│   ├── Contract/                              # असली फ्रेमवर्क कॉन्ट्रैक्ट टेस्ट (CI में अलग जॉब, नीचे देखें)
│   └── Support/FrameworkStubs.php             # टेस्ट के लिए फ्रेमवर्क क्लास स्टब
├── docs/
│   ├── mascot.svg                             # प्रोजेक्ट मस्कॉट "हैशी Hashy"
│   ├── architecture.svg                       # आर्किटेक्चर डिज़ाइन आरेख
│   ├── features.svg                           # फ़ीचर डिज़ाइन आरेख
│   └── lifecycle.svg                          # लाइफ़साइकिल आरेख
├── .github/workflows/release.yml              # main पर push करने पर स्वतः tag और release
├── composer.json
└── phpunit.xml.dist
```

> `vendor/`, `composer.lock` जैसे डिपेंडेंसी आर्टिफ़ैक्ट सूचीबद्ध नहीं हैं; `src/config/` **प्लगइन स्केलेटन** है और `config/` **पब्लिश करने योग्य कॉन्फ़िग नमूना** — दोनों का उद्देश्य अलग है।

## आर्किटेक्चर डिज़ाइन

![आर्किटेक्चर डिज़ाइन](./architecture.svg)

**चार परतों की एकदिशीय निर्भरता** — ऊपरी परत निचली परत पर निर्भर करती है, उल्टा नहीं:

| परत | ज़िम्मेदारी | स्थान |
|----|------|------|
| ऐप कॉल लेयर | Facade, कंटेनर / हेल्पर फ़ंक्शन, कंस्ट्रक्टर इंजेक्शन — तीन प्रवेश द्वार | बिज़नेस कोड |
| फ्रेमवर्क अडैप्टर लेयर | केवल वायरिंग: कंटेनर बाइंडिंग, कॉन्फ़िग पढ़ना, कॉन्फ़िग पब्लिश | `src/<Framework>/` |
| कोर लेयर | मल्टी-कनेक्शन प्रबंधन और इंस्टेंस निर्माण, **किसी भी फ्रेमवर्क पर निर्भर नहीं** | `src/HashidsManager.php`, `src/HashidsFactory.php` |
| निचली निर्भरता | वास्तविक encode/decode इम्प्लीमेंटेशन | `hashids/hashids` |

कोर लेयर पूरे पैकेज का केंद्र है: `HashidsManager` कॉन्फ़िग रखता है, `connection($name)` माँगने पर कनेक्शन बनाकर कैश करता है, और `__call()` बिना कनेक्शन बताए गए मेथड कॉल को डिफ़ॉल्ट कनेक्शन पर भेज देता है; `HashidsFactory::make()` ही एकमात्र जगह है जहाँ `Hashids\Hashids` बनता है। छह फ्रेमवर्क की अडैप्टर क्लासें मिलाकर लगभग 490 लाइन हैं (Facade, दो Hyperf फ़ैक्टरी और Yii 2 के ऐप कंपोनेंट सहित) — इनका काम केवल कोर को अपने-अपने कंटेनर से जोड़ना है।

## फ़ीचर डिज़ाइन

![फ़ीचर डिज़ाइन](./features.svg)

छह क्षमता समूह, सब एक ही कोर के इर्द-गिर्द:

- **encode/decode API**: `encode()` / `decode()` / `encodeHex()` / `decodeHex()`, जो `__call()` से डिफ़ॉल्ट कनेक्शन पर पहुँचते हैं — अलग से `connection()` की ज़रूरत नहीं।
- **मल्टी-कनेक्शन प्रबंधन**: `connection('alternative')` से स्विच; लेज़ी लोडिंग, एक ही कनेक्शन केवल एक बार बनता है।
- **मल्टी-फ्रेमवर्क अडैप्टेशन**: Laravel में `ServiceProvider`, Webman में `Install` + `Bootstrap`, ThinkPHP में `Service`, Hyperf में `ConfigProvider`, Yii 2 में `BootstrapInterface` + ऐप कंपोनेंट, Yii 3 में `ServiceProviderInterface` — हर एक अपने फ्रेमवर्क की परंपरा का पालन करता है।
- **कंटेनर बाइंडिंग**: क्लास नाम (`HashidsManager`, `HashidsFactory`, `Hashids\Hashids`) और स्ट्रिंग key (`'hashids'`, `'hashids.factory'`, `'hashids.connection'`) — दोनों ट्रैक से बाइंडिंग, छह फ्रेमवर्क में एक जैसी; आख़िरी दो स्ट्रिंग key vinkla/hashids के समान नाम की हैं, इसलिए माइग्रेशन आसान।
- **कॉन्फ़िग और पब्लिश**: छह फ्रेमवर्क के कॉन्फ़िग का **आकार एक जैसा** है — सब **फ्लैट ऐरे** (रूट लेवल पर `default` + `connections`), फ़र्क़ सिर्फ़ यह है कि कहाँ रखा जाता है — Laravel / Webman / ThinkPHP में `config/hashids.php`, Hyperf में `config/autoload/hashids.php`, Yii 2 में `config/params.php` के अंदर `params['hashids']`, Yii 3 में सीधे `ServiceProvider` के कंस्ट्रक्टर को (फ्रेमवर्क कॉन्फ़िग फ़ाइल की जगह तय नहीं करता)।
- **शून्य-फ्रेमवर्क-निर्भर कोर**: `new HashidsManager($config, $factory)` से सीधे काम चलता है; `$config` में नॉन-ऐरे भी स्वीकार्य है (खाली ऐरे में नॉर्मलाइज़ हो जाता है)।

## लाइफ़साइकिल

![लाइफ़साइकिल](./lifecycle.svg)

| चरण | क्या होता है |
|------|-----------|
| **इंस्टॉल चरण** | `composer require` पैकेज लाता है → Laravel ऑटो-डिस्कवरी / Webman इंस्टॉल हुक प्लगइन स्केलेटन कॉपी करता है / Yii 2 ऐप कॉन्फ़िग में `Bootstrap` या कंपोनेंट दर्ज करता है / Yii 3 `ContainerConfig::withProviders()` में `ServiceProvider` दर्ज करता है → `salt` / `length` / `alphabet` लोड होते हैं |
| **रनटाइम चरण** | कंटेनर `HashidsManager` रिज़ॉल्व करता है → `encode()` / `decode()` → `__call()` इसे `connection($name)` पर भेजता है → **कैश में मिले तो सीधे दोबारा उपयोग, न मिले तो `HashidsFactory::make()` से बनाकर `$connections` में लिखा जाता है** → शॉर्ट ID या मूल संख्या लौटती है |
| **अनइंस्टॉल चरण** | `composer remove` `Install::uninstall()` को ट्रिगर करता है, जो `config/plugin/erikwang2013/hashids` हटा देता है; **`config/hashids.php` बना रहता है** — साफ़ करना है या नहीं, यह उपयोगकर्ता तय करे |

कनेक्शन **माँगने पर ही बनते हैं**: जो प्रोसेस केवल डिफ़ॉल्ट कनेक्शन इस्तेमाल करती है, वह `alternative` जैसे अनुपयोगी कनेक्शन के निर्माण की कोई कीमत नहीं चुकाती।

## इंस्टॉलेशन

```bash
composer require erikwang2013/hashids
```

> **रनटाइम वातावरण**: निचली परत की `hashids/hashids` के लिए `ext-bcmath` या `ext-gmp` में से **कोई एक ज़रूरी** है, वरना पहली encode पर ही
> `RuntimeException: Missing math extension for Hashids` फेंका जाएगा। ये दोनों एक्सटेंशन `hashids/hashids` में केवल `suggest` में दर्ज हैं,
> जबकि Composer 2 अब suggest प्रिंट नहीं करता — इसलिए पतले इमेज (जैसे `php:8.3-fpm-alpine`) पर इंस्टॉल के समय कोई संकेत ही नहीं मिलता, धमाका रनटाइम में होता है,
> और Hashids इस्तेमाल करने वाली हर रिक्वेस्ट 500 देती है। इस पैकेज के `composer.json` के `suggest` में ये दोनों key जोड़ दिए गए हैं, फिर भी एक्सटेंशन चालू है या नहीं — यह आपको पुष्टि करना ही होगा।

## कॉन्फ़िग संरचना (Laravel / Webman / ThinkPHP / Yii 2 / Yii 3)

रिपॉज़िटरी की `config/hashids.php` के समान:

- `default`: डिफ़ॉल्ट कनेक्शन का नाम (जैसे `main`)।
- `connections`: कनेक्शन नाम => `salt`, `length`, वैकल्पिक `alphabet`।

> **Hyperf** की कॉन्फ़िग फ़ाइल का **पथ** अलग है (`config/autoload/hashids.php`), पर **संरचना समान** है — नीचे Hyperf अनुभाग देखें।
>
> **Yii 2** अलग कॉन्फ़िग फ़ाइल नहीं रखता — वही ऐरे ऐप की `config/params.php` में `params['hashids']` के अंदर रखा जाता है, नीचे Yii 2 अनुभाग देखें।
>
> **Yii 3** भी कॉन्फ़िग फ़ाइल नहीं पढ़ता; वही ऐरे `ServiceProvider` के कंस्ट्रक्टर को दे दें, नीचे Yii 3 अनुभाग देखें।

## बिना फ्रेमवर्क के उपयोग

मैनेजर को सीधे इंस्टैंशिएट किया जा सकता है:

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

[vinkla/hashids](https://github.com/vinkla/laravel-hashids) की तरह: कंटेनर में `HashidsManager` रजिस्टर होता है, मल्टी-कनेक्शन; डिफ़ॉल्ट कनेक्शन Facade और मेथड फ़ॉरवर्डिंग दोनों का समर्थन करता है।

Laravel 5.5+ इस पैकेज के `composer.json` में `extra.laravel` पढ़कर `HashidsServiceProvider` और Facade उपनाम `Hashids` स्वतः रजिस्टर कर देता है।

**कॉन्फ़िग पब्लिश करना (वैकल्पिक)**

```bash
php artisan vendor:publish --tag=hashids-config
```

यह `config/hashids.php` बनाता है। पब्लिश न करने पर पैकेज रजिस्ट्रेशन के समय अपनी अंतर्निहित डिफ़ॉल्ट कॉन्फ़िग मर्ज कर देता है।

**Facade (डिफ़ॉल्ट कनेक्शन)**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::encode(1, 2, 3);
$numbers = Hashids::decode($hash);
```

**कनेक्शन निर्दिष्ट करना**

```php
use Erikwang2013\Hashids\Laravel\Facades\Hashids;

$hash = Hashids::connection('alternative')->encode(100);
```

**`HashidsManager` का डिपेंडेंसी इंजेक्शन**

```php
use Erikwang2013\Hashids\HashidsManager;

public function __construct(private HashidsManager $hashids) {}

$this->hashids->encode(1);
$this->hashids->connection('alternative')->encode(2);
```

**निचली लेयर `Hashids\Hashids` का इंजेक्शन (डिफ़ॉल्ट कनेक्शन)**

```php
use Hashids\Hashids;

public function __construct(private Hashids $hashids) {}
```

Laravel इंटीग्रेशन चलाने के लिए प्रोजेक्ट में `laravel/framework` (और `illuminate/support` आदि) इंस्टॉल होना चाहिए। यह पैकेज `illuminate/*` को **require-dev** में रखता है, केवल अपने टेस्ट के लिए।

---

## Webman

इंस्टॉल के समय **`Install`** `config/plugin/erikwang2013/hashids` और रूट की **`config/hashids.php`** कॉपी करता है, तथा प्लगइन **bootstrap** Webman कंटेनर में `HashidsManager` रजिस्टर करता है।

अगर प्रोजेक्ट के `composer.json` में पहले से `support\Plugin::install` / `update` / `uninstall` हुक हैं, तो यह पैकेज इंस्टॉल करते समय इंस्टॉल स्क्रिप्ट स्वतः चल जाती है (`WEBMAN_PLUGIN = true`)।

**इंस्टॉल के बाद की फ़ाइलें**

- `config/plugin/erikwang2013/hashids/app.php`: `enable` स्विच।
- `config/plugin/erikwang2013/hashids/bootstrap.php`: `Erikwang2013\Hashids\Webman\Bootstrap` रजिस्टर करता है।
- `config/hashids.php`: मल्टी-कनेक्शन कॉन्फ़िग (पहली बार इंस्टॉल करने या ओवरराइट की पुष्टि करने पर लिखा जाता है)।

अगर स्वतः कॉपी नहीं हुई, तो पैकेज के अंदर से इन्हीं पथों की उदाहरण कॉन्फ़िग मैन्युअली कॉपी कर सकते हैं।

**प्लगइन बंद करना** (`config/plugin/erikwang2013/hashids/app.php`)

```php
<?php
return [
    'enable' => false,
];
```

**कंटेनर बाइंडिंग**

- `Erikwang2013\Hashids\HashidsManager` / `'hashids'`
- `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'`
- `Hashids\Hashids` / `'hashids.connection'` (डिफ़ॉल्ट कनेक्शन इंस्टेंस)

**कंट्रोलर उदाहरण**

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

**कनेक्शन निर्दिष्ट करना**

```php
$manager->connection('alternative')->encode(99);
```

Composer से पैकेज अनइंस्टॉल करने पर `Plugin::uninstall` चलता है, जो `config/plugin/erikwang2013/hashids` हटा देता है; `config/hashids.php` **नहीं** हटेगा — रखना है या नहीं, यह आप तय करें।

---

## ThinkPHP

**कस्टम सर्विस क्लास** के ज़रिए `HashidsManager` रजिस्टर होता है; कॉन्फ़िग वही **`config/hashids.php`** रहती है, जिसमें टॉप लेवल पर `default` और `connections` होते हैं।

**सर्विस रजिस्टर करना**

ऐप की **`config/service.php`** (TP संस्करण के अनुसार पथ बदल सकता है) के `services` में जोड़ें:

```php
<?php

return [
    // ...
    \Erikwang2013\Hashids\ThinkPHP\HashidsService::class,
];
```

अगर ऐप-लेवल `app/AppService.php` इस्तेमाल करते हैं, तो `register()` में समतुल्य बाइंडिंग लिख सकते हैं।

**कॉन्फ़िग फ़ाइल**

पैकेज के अंदर की `config/hashids.php` को ऐप की `config/hashids.php` में कॉपी करें (या उसी नाम की कॉन्फ़िग स्वयं मर्ज करें)।

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

**उपयोग उदाहरण**

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

ThinkPHP इंटीग्रेशन `think\Service` से विरासत लेता है, इसलिए इसे **`topthink/framework`** वातावरण में उपयोग करें (यह पैकेज इसे suggest में रखता है)।

---

## Hyperf

Composer का **`extra.hyperf.config`** `ConfigProvider` लोड करता है, जो कंटेनर में `HashidsFactory`, `HashidsManager` और डिफ़ॉल्ट कनेक्शन का `Hashids\Hashids` रजिस्टर करता है।

पैकेज के अंदर की **`config/autoload/hashids.php`** को प्रोजेक्ट की **`config/autoload/hashids.php`** में कॉपी करें (या प्रोजेक्ट का कॉन्फ़िग पब्लिश कमांड इस्तेमाल करें)।

इस फ़ाइल को **फ्लैट ऐरे** लौटाना चाहिए (रूट लेवल पर `default` + `connections`)।

Hyperf का `ConfigFactory` **फ़ाइल नाम** के आधार पर मर्ज करता है (`Arr::set($config, 'hashids', require $file)`), इसलिए `config('hashids')` ठीक फ़ाइल की सामग्री ही लौटाता है — **ऊपर एक और `'hashids' =>` लेयर न लगाएँ**। लगाने पर `connections` पहुँच से बाहर रह जाता है और कनेक्शन लेते समय `Hashids connection [main] is not configured` फेंका जाता है।

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

**कंटेनर बाइंडिंग**

| एब्स्ट्रैक्ट | इम्प्लीमेंटेशन |
|------|------|
| `Erikwang2013\Hashids\HashidsFactory` / `'hashids.factory'` | डिफ़ॉल्ट कंस्ट्रक्ट |
| `Erikwang2013\Hashids\HashidsManager` / `'hashids'` | `HashidsManagerFactory` |
| `Hashids\Hashids` / `'hashids.connection'` | `HashidsClientFactory` (डिफ़ॉल्ट कनेक्शन) |

**Controller / कंस्ट्रक्टर इंजेक्शन**

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

छह फ्रेमवर्क के कॉन्फ़िग की **संरचना एक जैसी** है (रूट लेवल पर `default` + `connections`), फ़र्क़ सिर्फ़ यह है कि कहाँ रखा जाता है: Hyperf **`config/autoload/hashids.php`** इस्तेमाल करता है, Laravel / Webman / ThinkPHP **`config/hashids.php`**, Yii 2 **`config/params.php` के `params['hashids']`** में रखता है, और Yii 3 सीधे `ServiceProvider` को देता है।

---

## Yii 2

अडैप्टर **`yii\base\BootstrapInterface`** को लागू करता है और छह आइडेंटिफ़ायर `Yii::$container` में रजिस्टर करता है; साथ ही **`yii\base\Component`** का सबक्लास भी देता है, ताकि Yii की परंपरागत `Yii::$app->hashids` सुविधा काम करे।

### दो प्रवेश द्वार, ज़रूरत के अनुसार चुनें (एक-दूसरे की जगह नहीं ले सकते)

**प्रवेश A: कंटेनर — `config/web.php` का `bootstrap` ऐरे**

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

**प्रवेश B: ऐप कंपोनेंट — `config/web.php` का `components` ऐरे**

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
Yii::$app->hashids->encode(1);                              // __call डिफ़ॉल्ट कनेक्शन पर भेजता है
Yii::$app->hashids->connection('alternative')->encode(100); // कनेक्शन बदलें
Yii::$app->hashids->manager;                                // नीचे का HashidsManager निकालें
```

> **दोनों प्रवेश द्वार क्यों ज़रूरी हैं**: `yii\di\ServiceLocator::get()` **कभी** `Yii::$container` पर वापस नहीं जाता,
> वह केवल अपने `_definitions` में दर्ज कंपोनेंट मानता है। इसलिए सिर्फ़ `bootstrap` रजिस्टर करने से `Yii::$app->hashids`
> काम नहीं करेगा; उल्टा, सिर्फ़ `components` सेट करने पर `Yii::$container->get()` इंस्टेंस नहीं देगा। जो चाहिए, वही कॉन्फ़िगर करें।

### कॉन्फ़िग फ़ाइल

Yii 2 में `config/hashids.php` जैसी कोई परंपरा नहीं है — कॉन्फ़िग ऐप की **`config/params.php`** में `params['hashids']` के अंदर रखा जाता है:

```php
return [
    // ...
    'hashids' => [
        'default'     => 'main',
        'connections' => [
            'main' => [
                // Yii 2 में Laravel जैसा ग्लोबल env() हेल्पर नहीं है, getenv()
                // या अपने ऐप का env मेकैनिज़्म इस्तेमाल करें।
                'salt'   => getenv('HASHIDS_SALT') ?: '',
                'length' => (int) (getenv('HASHIDS_LENGTH') ?: 0),
            ],
        ],
    ],
];
```

`Bootstrap` यही `Yii::$app->params['hashids']` पढ़ता है; यह key न मिले तो खाली ऐरे माना जाता है (डिफ़ॉल्ट कनेक्शन का नाम
`main` पर आ जाता है) और कोई एरर नहीं आता — लेकिन salt न होने पर हैश अनुमान लगाकर निकाले जा सकते हैं, इसलिए लाइव करने से पहले पुष्टि करें कि salt सेट है।

> **डिफ़ॉल्ट कनेक्शन लेज़ी रिज़ॉल्व होता है**: `bootstrap` चरण में केवल डेफ़िनिशन रजिस्टर होते हैं, असली कनेक्शन तब बनता है जब `Hashids\Hashids` माँगा जाए।
> कॉन्फ़िग ग़लत होने पर पूरा ऐप स्टार्ट फ़ेल नहीं होगा, बल्कि माँगने के समय `InvalidArgumentException` फेंका जाएगा।

Yii 2 इंटीग्रेशन `yii\base\BootstrapInterface` / `yii\base\Component` से विरासत लेता है, इसलिए इसे **`yiisoft/yii2`**
वातावरण में उपयोग करें (यह पैकेज इसे suggest में रखता है)।

---

## Yii 3

Yii 3 PSR-11 कंटेनर (`yiisoft/di`) से जुड़े पैकेजों का समूह है, जिसमें Yii 2 जैसा ऐप कॉन्फ़िग ऐरे नहीं होता।
अडैप्टर **`Yiisoft\Di\ServiceProviderInterface`** को लागू करने वाली क्लास है, जिसका `getDefinitions()` छह कंटेनर आइडेंटिफ़ायर लौटाता है।

**रजिस्ट्रेशन**

```php
use Erikwang2013\Hashids\Yii3\ServiceProvider;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;

$config = ContainerConfig::create()
    ->withProviders([
        // कॉन्फ़िग सीधे provider को दिया जाता है —— Yii 3 कॉन्फ़िग फ़ाइल की जगह तय नहीं करता,
        // मान कहाँ से आएँ (params, env, कॉन्फ़िग सेंटर) यह ऐप ख़ुद तय करता है।
        new ServiceProvider($params['hashids'] ?? []),
    ]);

$container = new Container($config);
```

**उपयोग**

```php
use Erikwang2013\Hashids\HashidsFactory;
use Erikwang2013\Hashids\HashidsManager;
use Hashids\Hashids;

$manager = $container->get(HashidsManager::class);
$hash    = $manager->encode(10, 20);                                  // डिफ़ॉल्ट कनेक्शन

$container->get('hashids')->connection('alternative')->encode(100);   // स्ट्रिंग उपनाम + कनेक्शन बदलना
$container->get(Hashids::class)->encode(1);                           // क्लास नाम से डिफ़ॉल्ट कनेक्शन
$container->get(HashidsFactory::class);                               // फ़ैक्टरी
```

कंटेनर में सिंगलटन सेमैंटिक्स पहले से हैं (एक ही id पर हर `get()` वही इंस्टेंस लौटाता है), इसलिए उपनाम और FQCN को एक ही कनेक्शन सेट मिलता है,
अडैप्टर के डेफ़िनिशन को केवल फ़ॉरवर्ड करना है, पहले से ऑब्जेक्ट बनाने की ज़रूरत नहीं।

> **एरर क्लास एक परत और लपेटी जाएगी**: डिफ़ॉल्ट कनेक्शन लेज़ी रिज़ॉल्व होता है, इसलिए असेंबली चरण में कुछ फ़ेल नहीं होगा; पर माँगने के समय कंटेनर इस पैकेज के
> `InvalidArgumentException` को **`Yiisoft\Di\BuildingException`** में लपेटकर फेंकता है —— `catch` में
> `InvalidArgumentException` लिखने से यह पकड़ में नहीं आएगा, `$e->getPrevious()` देखना होगा।

> **provider के डेफ़िनिशन साधारण definitions पर भारी पड़ते हैं**: असली कंटेनर पहले `definitions`, फिर `providers` असेंबल करता है,
> इसलिए hashids की बाइंडिंग बदलने के लिए `definitions` में उसी नाम की key बदलना **बेअसर** है, `extensions` इस्तेमाल करें या provider बदलें।

Yii 3 इंटीग्रेशन `Yiisoft\Di\ServiceProviderInterface` को लागू करता है, इसलिए इसे **`yiisoft/di`** वातावरण में उपयोग करें
(यह पैकेज इसे suggest में रखता है)।

---

## प्रोजेक्ट मस्कॉट

प्रोजेक्ट मस्कॉट **हैशी Hashy** है — सिर पर एंटीना और सीने पर `#` लिए एक गोल-कोने वाला छोटा ब्लॉक कैरेक्टर; वेक्टर चित्र देखें [`docs/mascot.svg`](../../mascot.svg)।

टर्मिनल में SVG नहीं दिखता, इसलिए कोड में इसका समतुल्य ASCII संस्करण रखा गया है (`Erikwang2013\Hashids\Mascot`):

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

Webman प्लगइन पहली बार `config/hashids.php` पब्लिश करते समय यह अभिवादन एक बार प्रिंट करता है; कॉन्फ़िग पहले से मौजूद हो (जैसे बार-बार `composer update` चलाने पर) तो दोबारा परेशान नहीं करता।

---

## ओपन सोर्स आसान नहीं है, आपके समर्थन का स्वागत है

<p align="center">
  <img src="../../weixinpay.png" alt="WeChat Pay" width="130" height="130" />
  &nbsp;&nbsp;&nbsp;&nbsp;
  <img src="../../alipay.png" alt="Alipay" width="130" height="130" />
</p>

<p align="center"><strong>WeChat Pay</strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Alipay</strong></p>

---


## License

MIT. See [LICENSE](../../../LICENSE).
