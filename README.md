# Модуль для приема оплаты для Magento 2
## Инструкция по подключению

### Инструкция по настройке счета

1. Зарегистрируйтесь в платёжной системе PayAnyWay и заполните все необходимые данные. Дождитесь проверки аккаунта и создайте расширенный счет.

2. Заполните настройки расширенного счета (раздел «Мой счет» - «Управление счетами» -«Редактировать счет»):
    - «Тестовый режим»: Нет
    - «Check URL»: заполнять не нужно
    - «Pay URL»: http://ваш_сайт/payanyway/url/mntsuccess (если ваш сайт имеет сертификат SSL, то вместо __http://__ нужно писать __https://__).
    - «HTTP метод»: POST
    - «Можно переопределять настройки в url»: Нет
    - «Подпись формы оплаты обязательна»: Да
    - «Код проверки целостности данных»: придумайте и запомните ваш код (произвольный набор символов)
    - «Success URL»: заполнять не нужно
    - «Fail URL»: заполнять не нужно
    - «InProgress URL»: Необязательное поле. Подробнее смотрите в документации: [https://www.moneta.ru/doc/MONETA.Assistant.ru.pdf](https://www.moneta.ru/doc/MONETA.Assistant.ru.pdf)
    - «Return URL»: Необязательное поле. URL страницы магазина, куда должен вернуться покупатель при добровольном отказе от оплаты. Отчет об оплате в этом случае магазину не отсылается.

    ___Внимание! Для кириллического домена PayURL должен быть указан в кодировке Punycode.___

### Модуль

1. Скачайте модуль и установите его через инсталлятор Вашего сайта либо вручную скопировав содержимое архива в корневой каталог сайта. Архив с модулем доступен по адресу [https://github.com/PayAnyWay/Magento-2/tree/master/arc](https://github.com/PayAnyWay/Magento-2/tree/master/arc) (всегда используйте модуль самой последней версии).

2. Для фискализации чеков по 54-ФЗ настройте вашу кассу в сервисе [https://kassa.payanyway.ru](https://kassa.payanyway.ru), в настройках Вашего расширенного счёта в Монета.ру установите Pay URL: __https://kassa.payanyway.ru/index.php?do=invoicepayurl__, а в настройках кассы в kassa.payanyway.ru пропишите ссылку на Pay URL Вашего интернет-магазина. _В этом случае будет пробиваться чек по 54-ФЗ через сервис kassa.payanyway.ru, а запрос на Pay URL магазина будет проходить транзитом через сервис kassa.payanyway.ru._

Удачных платежей!