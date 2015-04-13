# ProxyManagerBundle
Менеджер для контроля за использованием прокси-серверов.

Менеджер позволяет обеспечить задержку между использованием каждого прокси, а также отслеживать сервера, которые перестали работать. 

Менеджер можно использовать как обычную библиотеку, так и как бандл для Symfony.

## Установка
Для установки нужно использовать composer:
```
composer require "cosmologist/proxy-manager-bundle" "dev-master"
```

## Простой пример использования
```php

// Список серверов
$proxies = [
	'1.1.1.1:80',
    '2.2.2.2:8080'
];

// Сколько ждать секунд перед повторным использование прокси
$minAccessPeriod = 2;

// Через сколько неудачных попыток считаем прокси нерабочим
$maxFailedAccessCount = 2;

$proxyManager = new Cosmologist\ProxyManagerBundle\Service($proxies, $minAccessPeriod, $maxFailedAccessCount);

// Если менеджер используется в виде бандла в Symfony-приложении, то можно использовать соответствующий сервис
// $this->proxyManager = $this->getContainer()->get('cosmologist.proxy_manager');
// $this->proxyManager->setProxies($proxies);
// $this->proxyManager->setMinAccessPeriod($minAccessPeriod);
// $this->proxyManager->setMaxFailedAccessCount($maxFailedAccessCount);

try {
	$proxy = $proxyManager->getProxy();
    
    // Получаем адрес прокси-сервера
    $proxyAddress = $proxy->getAddress();
    
    // Что-то делаем через прокси, к примеру, скачиваем страницу
    ...
    
    // Если результат скачивания неудачный, фиксируем, что для данного прокси была неудачная попытка
    $proxy->increaseFailedAttemptsCount();
    
    
} catch (ProxiesEndedException $e) {
	echo 'Нет доступных для использования прокси-серверов';
}
```

## Пример многопоточного скачивание сайта с использованием списка прокси-серверов
Если вам требуется выкачать большое количество файлов или страниц с определенного сайта - будьте готовы к тому, что администраторы ресурса могут заблокировать доступ, при большом количестве запросов или при слишком частых запросов с одного IP-адреса. Для обхода таких блокировок пригодится ProxyManagerBundle, который позволит обращаться к ресурсу через каждый прокси-сервер с заданной периодичностью, а также отслеживать прокси, которые перестали работать.
Для ускорения скачивания будем скачивать в несколько потоков, для этого можно использовать популярную библиотеку [Guzzle](https://github.com/guzzle/guzzle).

```php
$guzzle = new GuzzleHttp\Client();

// Список серверов
$proxies = [
	'1.1.1.1:80',
    '2.2.2.2:8080'
];

$proxyManager = new Cosmologist\ProxyManagerBundle\Service($proxies);

// Нельзя использовать переменную c именем $this в блоке use анонимной функции
$that = $this;

// Настройки пула для Guzzle
$options = [
    'before' => function (BeforeEvent $event) use ($proxyManager) {

        $proxy = $proxyManager->getProxy()->getAddress();
        $event->getRequest()->getConfig()->set('proxy', $proxy);

        echo sprintf("Set proxy %s for %s\n", $proxy, $event->getRequest()->getUrl());
    },
    'complete' => function (CompleteEvent $event) use ($that) {
        echo 'Completed request to ' . $event->getRequest()->getUrl() . "\n";

        $that->parsePage($event->getResponse());
    },
    'error' => function (ErrorEvent $event) use ($proxyManager) {
        $proxyAddress = $event->getRequest()->getConfig()->get('proxy');
        if ($proxy = $proxyManager->findProxyByAddress($proxyAddress)) {
            $proxy->increaseFailedAttemptsCount();
        }

        echo sprintf("Request failed to %s with proxy %s\n", $event->getRequest()->getUrl(), $proxyAddress);
    },
    'pool_size' => 100
];

// Формируем набор HTTP-запросов
$requests = [
    $guzzle->createRequest('GET', 'http://example.com/first.html', ['connect_timeout' => 10, 'timeout' => 20]),
    $guzzle->createRequest('GET', 'http://example.com/second.html', ['connect_timeout' => 10, 'timeout' => 20]),
    ...
    $guzzle->createRequest('GET', 'http://example.com/last.html', ['connect_timeout' => 10, 'timeout' => 20])
];

// Запускаем асинхронное скачивание
Pool::batch($guzzle, $requests, $options);
```