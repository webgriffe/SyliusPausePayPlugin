<p align="center">
    <a href="https://sylius.com" target="_blank">
        <img src="https://demo.sylius.com/assets/shop/img/logo.png" />
    </a>
</p>

<h1 align="center">Sylius <a href="https://pausepay.it/" target="_blank">PausePay</a> Plugin</h1>

<p align="center">Sylius plugin for PausePay payment method.</p>


## Installation

1. Run:
    ```bash
    composer require webgriffe/sylius-pausepay-plugin
    ```

2. Add `Webgriffe\SyliusPausePayPlugin\WebgriffeSyliusPausePayPlugin::class => ['all' => true]` to your `config/bundles.php`.
   
    Normally, the plugin is automatically added to the `config/bundles.php` file by the `composer require` command. If it is not, you have to add it manually.

3. Create a new file config/packages/webgriffe_sylius_pausepay_plugin.yaml:
    ```yaml
    imports:
      - { resource: "@WebgriffeSyliusPausePayPlugin/config/config.php" }
    ```

4. Create a new file config/routes/webgriffe_sylius_pausepay_plugin.yaml:
    ```yaml
    webgriffe_sylius_pausepay_plugin_shop:
        resource: "@WebgriffeSyliusPausePayPlugin/config/routes/shop.php"
        prefix: /{_locale}
        requirements:
            _locale: ^[A-Za-z]{2,4}(_([A-Za-z]{4}|[0-9]{3}))?(_([A-Za-z]{2}|[0-9]{3}))?$
    
    webgriffe_sylius_pausepay_plugin_index:
        resource: "@WebgriffeSyliusPausePayPlugin/config/routes/index.php"
    
    webgriffe_sylius_pausepay_plugin_payum:
        resource: "@WebgriffeSyliusPausePayPlugin/config/routes/payum.php"
    
    sylius_shop_payum_cancel:
        resource: "@PayumBundle/Resources/config/routing/cancel.xml"
    ```

5. Add the PaymentOrder entity. Create a new file `src/Entity/Payment/PaymentOrder.php` with the following content:
    ```php
    <?php

    declare(strict_types=1);

    namespace App\Entity\Payment;

    use Doctrine\ORM\Mapping as ORM;
    use Webgriffe\SyliusPausePayPlugin\Entity\PaymentOrder as BasePaymentOrder;
    
    /**
     * @ORM\Entity
     * @ORM\Table(name="webgriffe_sylius_pausepay_payment_order")
     */
    class PaymentOrder extends BasePaymentOrder
    {
    }

    ```

6. Add a new CompanyInfoResolver service that decorates the dummy service `webgriffe_sylius_pausepay.resolver.company_info` of the plugin. This service is used to resolve the company info "of the order" and will be passed to PausePay system upon payment creation.

   Create a new file `src/Resolver/PausePay/CompanyInfoResolver.php` with the following content:
    ```php
    <?php
    
    declare(strict_types=1);
    
    namespace App\Resolver\PausePay;
    
    use Sylius\Component\Core\Model\OrderInterface;
    use Webgriffe\SyliusPausePayPlugin\Resolver\CompanyInfoResolverInterface;
    use Webgriffe\SyliusPausePayPlugin\ValueObject\CompanyInfo;
    
    final class CompanyInfoResolver implements CompanyInfoResolverInterface
    {
        public function resolveFromOrder(OrderInterface $order): CompanyInfo
        {
            // TODO: your logic here
        }
    }
    ```

    and define it as the decoration/replacement of the service with alias `webgriffe_sylius_pausepay.resolver.company_info`. For example create the file `config/services/resolver.yaml` with content:

    ```yaml
    services:
        webgriffe_sylius_pausepay.resolver.company_info:
            class: App\Resolver\PausePay\CompanyInfoResolver
    ```

    TODO: Add sandbox logic

7. If you have a custom implementation of the Sylius service `PaymentMethodResolver`, you must add the logic to check the availability of PausePay payment method by using the service `PausePayAvailabilityChecker`.

   TODO: Add example

8. Run:
    ```bash
    php bin/console doctrine:migrations:migrate
    ```

9. Run:
    ```bash
    php bin/console sylius:install:assets
   ```
   Or, you can add the entry to your webpack.config.js file:
    ```javascript
    .addEntry(
        'webgriffe-sylius-pausepay-entry',
        './vendor/webgriffe/sylius-pausepay-plugin/public/poll_payment.js'
    )
    ```
   And then override the template `WebgriffeSyliusPausePayPlugin/after_pay.html.twig` to include the entry:
    ```twig
    {% block javascripts %}
        {{ parent() }}

        <script>
            window.afterUrl = "{{ afterUrl }}";
            window.paymentStatusUrl = "{{ paymentStatusUrl }}";
        </script>
        {{ encore_entry_script_tags('webgriffe-sylius-pausepay-entry', null, 'sylius.shop') }}
    {% endblock %}
    ```

## Usage

Access to the admin panel and go to the `Payment methods` section. Create a new payment method and select `PausePay`
as gateway. Then, configure the payment method with the required parameters.

## Contributing

For a comprehensive guide on Sylius Plugins development please go to Sylius documentation,
there you will find the <a href="https://docs.sylius.com/en/latest/plugin-development-guide/index.html">Plugin Development Guide</a>, that is full of examples.

### Quickstart Installation

#### Traditional

1. Run `composer create-project sylius/plugin-skeleton ProjectName`.

2. From the plugin skeleton root directory, run the following commands:

    ```bash
    $ (cd tests/Application && yarn install)
    $ (cd tests/Application && yarn build)
    $ (cd tests/Application && APP_ENV=test bin/console assets:install public)
    
    $ (cd tests/Application && APP_ENV=test bin/console doctrine:database:create)
    $ (cd tests/Application && APP_ENV=test bin/console doctrine:schema:create)
    ```

To be able to set up a plugin's database, remember to configure you database credentials in `tests/Application/.env` and `tests/Application/.env.test`.

#### Docker

1. Execute `docker compose up -d`

2. Initialize plugin `docker compose exec app make init`

3. See your browser `open localhost`

## Usage

#### Running plugin tests

  - PHPUnit

    ```bash
    vendor/bin/phpunit
    ```

  - PHPSpec

    ```bash
    vendor/bin/phpspec run
    ```

  - Behat (non-JS scenarios)

    ```bash
    vendor/bin/behat --strict --tags="~@javascript"
    ```

  - Behat (JS scenarios)
 
    1. [Install Symfony CLI command](https://symfony.com/download).
 
    2. Start Headless Chrome:
    
      ```bash
      google-chrome-stable --enable-automation --disable-background-networking --no-default-browser-check --no-first-run --disable-popup-blocking --disable-default-apps --allow-insecure-localhost --disable-translate --disable-extensions --no-sandbox --enable-features=Metal --headless --remote-debugging-port=9222 --window-size=2880,1800 --proxy-server='direct://' --proxy-bypass-list='*' http://127.0.0.1
      ```
    
    3. Install SSL certificates (only once needed) and run test application's webserver on `127.0.0.1:8080`:
    
      ```bash
      symfony server:ca:install
      APP_ENV=test symfony server:start --port=8080 --dir=tests/Application/public --daemon
      ```
    
    4. Run Behat:
    
      ```bash
      vendor/bin/behat --strict --tags="@javascript"
      ```
    
  - Static Analysis
  
    - Psalm
    
      ```bash
      vendor/bin/psalm
      ```
      
    - PHPStan
    
      ```bash
      vendor/bin/phpstan analyse -c phpstan.neon -l max src/  
      ```

  - Coding Standard
  
    ```bash
    vendor/bin/ecs check
    ```

#### Opening Sylius with your plugin

- Using `test` environment:

    ```bash
    (cd tests/Application && APP_ENV=test bin/console sylius:fixtures:load)
    (cd tests/Application && APP_ENV=test bin/console server:run -d public)
    ```
    
- Using `dev` environment:

    ```bash
    (cd tests/Application && APP_ENV=dev bin/console sylius:fixtures:load)
    (cd tests/Application && APP_ENV=dev bin/console server:run -d public)
    ```
