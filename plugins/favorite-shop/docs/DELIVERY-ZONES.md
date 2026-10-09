# Favorite Shop delivery zones and international address customization

## Customer checkout
- Division/State/Province and District/City are optional configurable selectors, not mandatory fields.
- Country selection controls which address labels and region lists are shown (for example Bangladesh: Division and District; another country: State/Province and City/County).
- Keep the address line and phone available even when structured regions are skipped.
- Region lists are admin-editable per country; do not hardcode Bangladesh as the only supported country.
- If an administrator has not configured a country's region list, show simple optional text fields rather than a broken or empty required dropdown.
- Selecting a region may help calculate delivery charges, but customer may continue with the minimum required delivery address fields.

## Delivery rate rules
- Admin can create named delivery zones/rules with country and optional region/city matching.
- Bangladesh starter rules: Inside Dhaka, Outside Dhaka, and optionally custom district/area rules.
- Each rule has a configurable flat charge, optional free-shipping threshold override, enabled flag, and priority.
- The most specific matching enabled rule wins (city/area > district > division/state > country > default).
- Admin can configure a default fallback charge for addresses that do not match a specific zone.
- Rates and labels are configuration data; no delivery amount is hardcoded.
- Calculate the final delivery charge server-side from the saved rule, then snapshot the matched zone and amount on the order so later rule changes don't alter old orders.
- Automatic free-delivery offers and valid free-shipping coupons can reduce the selected rate to zero; show the reason in the checkout summary.
- Missing optional region selection must not block checkout. If no zone can be confidently matched, use the configured fallback and clearly show the charge before order submission.

## Currency and country independence
- Shipping prices are stored in integer minor units with an explicit currency code.
- Country, region labels, region options, zones, and rates can be configured separately.
- Country-aware address fields are a plugin feature; do not alter Favorite CMS core or Favorite Digital.
