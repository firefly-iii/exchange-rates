# Firefly III exchange rates collector

This script collects selected exchange rates from [exchangerate.host](https://exchangerate.host) and stores them in
an Azure Storage container. Firefly III v5.8 and upwards can download these files and use them to calculate exchange
rates in Firefly III.

## Limitations

The currencies included in this service are limited to the currencies that are enabled by default in Firefly III. 
Cryptocurrency exchange rates are not downloaded.

Your Firefly III installation will only download exchange rates for the currencies you have enabled. If you enable a new currency,
exchange rates may not be available. The next run of your [cron job](https://docs.firefly-iii.org/how-to/firefly-iii/advanced/cron/), if configured correctly, will download them.

<!-- HELP TEXT -->

## Do you need help, or do you want to get in touch?

Do you want to contact me? You can email me at [james@firefly-iii.org](mailto:james@firefly-iii.org) or get in touch through one of the following support channels:

- [GitHub Discussions](https://github.com/firefly-iii/firefly-iii/discussions/) for questions and support
- [Gitter.im](https://gitter.im/firefly-iii/firefly-iii) for a good chat and a quick answer
- [GitHub Issues](https://github.com/firefly-iii/firefly-iii/issues) for bugs and issues
- <a rel="me" href="https://fosstodon.org/@ff3">Mastodon</a> for news and updates

<!-- END OF HELP TEXT -->

<!-- SPONSOR TEXT -->

## Support the development of Firefly III

If you like Firefly III and if it helps you save lots of money, why not send me a dime for every dollar saved! 🥳

OK that was a joke. If you feel Firefly III made your life better, please consider contributing as a sponsor. Please check out my [Patreon](https://www.patreon.com/jc5) and [GitHub Sponsors](https://github.com/sponsors/JC5) page for more information. You can also [buy me a ☕️ coffee at ko-fi.com](https://ko-fi.com/Q5Q5R4SH1). Thank you for your consideration.

<!-- END OF SPONSOR TEXT -->
