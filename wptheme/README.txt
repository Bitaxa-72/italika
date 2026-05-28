ECOMCARD / FAVORITES

1. СТРУКТУРА

Файлы:
- functions.php
- inc/ecomcard.php
- modules/favorites/favorites.php
- modules/favorites/assets/favorites.js

Подключение в functions.php:
- require_once get_template_directory() . '/modules/favorites/favorites.php';
- require_once get_template_directory() . '/inc/ecomcard.php';

2. ЧТО ДЕЛАЕТ ECOMCARD

italika_ecomcard_render($product_id, $args = [], $echo = false)

Функция рендерит единую карточку товара WooCommerce.
Карточка используется как общий источник истины для всех секций сайта:
- акции
- каталог
- похожие товары
- новинки
- избранное
- результаты поиска

3. БАЗОВЫЙ ВЫЗОВ

echo italika_ecomcard_render($product_id);

Если внутри стандартного WooCommerce loop:

echo italika_ecomcard_render(get_the_ID());

4. ЛОГИКА КАРТОЧКИ

Карточка автоматически выводит:
- бейдж "Акция", если у товара есть sale price
- кнопку избранного
- изображение товара
- название товара
- статус наличия:
  - "В наличии"
  - "Ожидается поступление"
- текущую цену
- старую цену, если товар акционный
- процент скидки
- экономию в абсолютном значении
- кнопку добавления в корзину или disabled-состояние, если товара нет

5. ИЗБРАННОЕ

Избранное хранится в user_meta:
- ключ: _italika_favorite_product_ids

Если пользователь не авторизован:
- клик по сердцу имитирует клик по кнопке ЛК в шапке

Если пользователь авторизован:
- товар добавляется или удаляется из избранного через AJAX
- активное состояние сердечка рендерится на сервере

Основные функции:
- italika_favorites_get_user_ids($user_id = 0)
- italika_favorites_save_user_ids($ids, $user_id = 0)
- italika_favorites_is_favorite($product_id, $user_id = 0)
- italika_favorites_toggle($product_id, $user_id = 0)
- italika_favorites_get_count($user_id = 0)

6. СЧЁТЧИК В ХЕДЕРЕ

Для авторизованного пользователя количество избранных товаров берется через:
- italika_favorites_get_count()

Для гостя счётчик не выводится.

7. ВАЖНО

Карточку не дублировать по шаблонам.
Во всех секциях использовать только:
- italika_ecomcard_render()

Не собирать HTML карточки строками в JS.
JS используется только для поведения:
- toggle избранного
- ajax-логика
- интерактивные состояния

8. ЗАМЕЧАНИЯ

Текущая карточка в первую очередь рассчитана на стандартные WooCommerce simple products.
Для variable/grouped/external товаров поведение кнопки корзины зависит от WooCommerce-логики конкретного типа товара.