# Firefly III exchange rates collector

This script collects selected exchange rates from [exchangerate.host](https://exchangerate.host) and stores them in
an Azure Storage container. Firefly III v5.8 and upwards can download these files and use them to calculate exchange
rates in Firefly III.

## Limitations

The currencies included in this service are limited to the currencies that are enabled by default in Firefly III. 
Cryptocurrency exchange rates are not downloaded.

Your Firefly III installation will only download exchange rates for the currencies you have enabled. If you enable a new currency,
exchange rates may not be available. The next run of your [cron job](https://docs.firefly-iii.org/how-to/firefly-iii/advanced/cron/), if configured correctly, will download them.

## Contact

You can contact me at [james@firefly-iii.org](mailto:james@firefly-iii.org), you may open an issue or contact me through the support channels:

- [GitHub Discussions for questions and support](https://github.com/firefly-iii/firefly-iii/discussions/)
- [Gitter.im for a good chat and a quick answer](https://gitter.im/firefly-iii/firefly-iii)
- [GitHub Issues for bugs and issues](https://github.com/firefly-iii/firefly-iii/issues)
- [Follow me around for news and updates on Mastodon](https://fosstodon.org/@ff3)

<!-- SPONSOR TEXT -->
## Donate

If you feel Firefly III made your life better, consider contributing as a sponsor. Please check out my [Patreon](https://www.patreon.com/jc5) and [GitHub Sponsors](https://github.com/sponsors/JC5) page for more information. Thank you for considering.

<!-- END OF SPONSOR -->
