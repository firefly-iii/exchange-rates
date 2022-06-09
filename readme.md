# Firefly III exchange rates collector

This script collects selected exchange rates from [exchangerate.host](https://exchangerate.host) and stores them in
an Azure Storage container. Firefly III v5.8 and upwards can download these files and use them to calculate exchange
rates withing Firefly III.

## Limitations

The exchange rates I provide are listed [here](https://github.com/firefly-iii/exchange-rates/blob/main/run.php#L36) and
are limited to the currencies that are enabled by default in Firefly III. Cryptocurrency exchange rates are not downloaded.

## Contact

You can contact me at [james@firefly-iii.org](mailto:james@firefly-iii.org), you may open an issue or contact me through the support channels:

- [GitHub Discussions for questions and support](https://github.com/firefly-iii/firefly-iii/discussions/)
- [Gitter.im for a good chat and a quick answer](https://gitter.im/firefly-iii/firefly-iii)
- [GitHub Issues for bugs and issues](https://github.com/firefly-iii/firefly-iii/issues)
- [Follow me around for news and updates on Twitter](https://twitter.com/Firefly_iii)

<!-- SPONSOR TEXT -->
## Donate

If you feel Firefly III made your life better, consider contributing as a sponsor. Please check out my [Patreon](https://www.patreon.com/jc5) and [GitHub Sponsors](https://github.com/sponsors/JC5) page for more information. Thank you for considering.

<!-- END OF SPONSOR -->
