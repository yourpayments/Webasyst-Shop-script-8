-------
#Webasyst-Shop-script-8
======

##Установка и проверка

Установка модуля может быть выполнена двумя способами

1. Автоматическая установка модуля с помощью “Инсталлера“

Для установки модуля на сайт необходимо зайти в пункт меню “Инсталлер”, и с помощью поиска найти и установить модуль «PayU». Так же, если необходим функционал для выполнения возвратов по проведенным платежам, необходимо найти и установить плагин «Получение статусов платежей PayU». После установки плагина необходимо выполнить его настройку.

2. Ручная установка модуля

Для устновки модуля вам нужно скопировать папки:

payu в /wa-plugins/payment
payu в /wa-apps/shop/plugins/
Зайти в контрольную панель Webasyst и настроить код продавца и секретный ключ дополнительно, лучше сразу включить тестовый режим с включюнным тестовым режимом вам нужно попробовать купить что то у себЯ на сайте после успешного тестового платежа вам нужно будет написать письмо вашему менеджеру в PayU

Так же в контрольной панели необходимо активировать плагин payu (Инсталлер > Установлено > Возврат платежей через PayU > Включить)
Укажите IPN вида http://yourdomain/payments.php/PayU/?transaction_result=result
в личном кабинете PayU на странице https://secure.payu.ru/cpanel/ipn_settings.php 
