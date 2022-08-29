# Code Samples

Included in this repo are 3 unrelated projects displaying common WordPress code. One project is a small post re-ordering plugin in the "kdreorder" directory. The other directory "ajax-samples" contains the JS and PHP files for an AJAX call using the WP Rest API. The WooCommerce directory shows some theme template files overwriting the default Woo template.

## KDReorder

I built this plugin a few months ago for a client to sort their custom post_type objects. The plugin allows them to choose a custom taxonomy term and then drag and drop posts to specify a specific order for that term. I had previously made them a custom “inventory” post_type, in a child them, so this was a minor update mainly built to display their inventory items when the other re-ordering plugin they were using stopped working.

## AJAX Samples

This code was developed to dismiss a tutorial that first-time users would see upon visiting a website. Since users are receiving video training they may want to bypass the brief tutorial. Clicking on the "Dismiss Tutorial" button (.dismiss-tutorial) on the front-end initiates the action.

## WooCommerce

The WooCommerce samples show the code required to create a custom product single template for a WooCommerce product that is selling Zoom meetings. The Zoom meetings are managed by a WooCommerce extension plugin called eRoom. Since the website is only in English I did not use any multilingual functions (`_()` or `_e()`) for any of the text. In order to get information for the template, I used a few custom MySQL queries to grab the information from the Zoom meeting post meta data. The page design was provided in a FIGMA file and I created it exactly as provided. 
