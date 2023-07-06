# DeckCommerce_Integration Module

Extension extends the functionality of Magento to work with Deck Commerce OMS (https://www.deckcommerce.com).

It has the following features:

- Allows to import inventory from the Deck Commerce side to Magento (full and delta inventory import)
- Check inventory on-the-fly on the product, cart and checkout pages
- Export Magento order to the Deck Commerce
- Show the Deck Commerce orders, shipments and returns information in the Magento customer’s account in real time.
- Show the Deck Commerce orders information for guest customer in real time.
- Create and cancel RMA on Deck Commerce (only in Magento Enterprise Edition)

## Requirements
  * Magento Enterprise Edition 2.3.x-2.4.x

## Configuration in Admin Panel

In the left menu: Deck Commerce 

- Manage Shipping Methods - Allows to create Shipping methods used on Deck Commerce side and use them for mapping with Magento shipping methods
- Map Shipping Methods - Allows to create mapping between Magento and Deck Commerce shipping methods
- Inventory Import - Shows the history of Full and Delta inventory imports 
- Configurations - Link to system configuration of extension

## Installation

To install Deck Commerce Integration, create a new directory app/code/DeckCommerce/Integration/ 
and copy there the contents of the unzipped DeckCommerce_Integration/ folder.
Finally the folders structure must be the following: app/code/DeckCommerce/Integration/[registration.php and other module files].

## Extension Activation

In your Command Line Interface run the following command:

```
bin/magento setup:upgrade --keep-generated
```

If you are using a production mode, also run commands:

```
bin/magento setup:static-content:deploy
bin/magento setup:di:compile
```
