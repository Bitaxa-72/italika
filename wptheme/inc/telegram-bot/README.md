# Italika Telegram order bot

Этот модуль живет внутри темы, но секреты хранит вне темы.

## 1. Создать бота

1. Откройте Telegram и найдите `@BotFather`.
2. Отправьте `/newbot`.
3. Укажите имя и username бота.
4. BotFather выдаст token. Его нельзя публиковать, коммитить или отправлять в чат.

## 2. Добавить секреты в `wp-config.php`

Добавьте рядом с остальными `define(...)`, выше строки `/* That's all, stop editing! */`:

```php
define('ITALIKA_TG_BOT_TOKEN', '1234567890:AA...ваш_токен_от_BotFather');
define('ITALIKA_TG_WEBHOOK_SECRET', 'замените-на-длинную-случайную-строку-32-64-символа');
```

`ITALIKA_TG_WEBHOOK_SECRET` нужен, чтобы чужой человек не мог отправлять команды в REST endpoint сайта.

## 3. Включить webhook Telegram

Замените `<TOKEN>`, `<SITE>` и `<SECRET>`:

```bash
curl -X POST "https://api.telegram.org/bot<TOKEN>/setWebhook" \
  -d "url=https://<SITE>/wp-json/italika/v1/telegram/webhook" \
  -d "secret_token=<SECRET>"
```

`<SECRET>` должен полностью совпадать с `ITALIKA_TG_WEBHOOK_SECRET`.

Проверить webhook:

```bash
curl "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

## 4. Подключить чат

1. Добавьте бота в нужный Telegram-чат.
2. Напишите в этом чате `/start`.
3. Бот ответит, что включен.
4. После новых заказов WooCommerce бот будет присылать сообщение с клиентом, способом получения, оплатой, составом и суммой.

Остановить отправку в конкретный чат:

```text
/stop
```

## 5. Что считается новым заказом

Уведомление отправляется на хуке `woocommerce_checkout_order_processed`, то есть после оформления заказа на checkout. Для защиты от дублей у заказа ставится meta `_italika_tg_order_sent = yes`.

## 6. Где лежат данные

- Токен и webhook secret: только в `wp-config.php` или переменных окружения.
- Активные Telegram-чаты: WordPress option `italika_tg_active_chats`.
- Маркер отправки по заказу: order meta `_italika_tg_order_sent`.
