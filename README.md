# Code Samples

Included in this repo are 4 unrelated projects displaying common WordPress code. One project is a small post re-ordering plugin in the "kdreorder" directory. The other directory "ajax-samples" contains the JS (jQuery) and PHP files for an AJAX call using the WP Rest API. The WooCommerce directory shows some theme template files overwriting the default Woo template. The "js" directory contains a mobile-nav-menu.js file, which is responsibe for dynamic menu functionality on kofflerboats.com. These samples are a little dated in that they show a fraction of my coding capability, and also in the code formatting - see the PSR-Standards.md file for more on that. Now I use the WordPress coding standards for WordPress projects. That being said, I'm extremely flexible and will adopt whatever coding standards a team is using, or that a framework provides and will configure my prettier (VS Code extension) to output clean code. 

## KDReorder

I built this plugin a few years ago for a client to sort their custom post_type objects. The plugin allows them to choose a custom taxonomy term and then drag and drop posts to specify a specific order for that term. I had previously made them a custom “inventory” post_type, in a child them, so this was a minor update mainly built to display their inventory items when the other re-ordering plugin they were using stopped working.

## AJAX Samples

This code was developed to dismiss a tutorial that first-time users would see upon visiting a website. Since users are receiving video training they may want to bypass the brief tutorial. Clicking on the "Dismiss Tutorial" button (.dismiss-tutorial) on the front-end initiates the action.

## WooCommerce

The WooCommerce samples show the code required to create a custom product single template for a WooCommerce product that is selling Zoom meetings. The Zoom meetings are managed by a WooCommerce extension plugin called eRoom. Since the website is only in English I did not use any multilingual functions (`_()` or `_e()`) for any of the text. In order to get information for the template, I used a few custom MySQL queries to grab the information from the Zoom meeting post meta data. The page design was provided in a FIGMA file and I created it exactly as provided. 

## JS Sample

The JS folder contains a Vanila JavaScript (ES6+) file that manages the main navigation menu for Kofflerboats. The menu has been optimized to display differently for PC/tablet vs mobile phone. On Mobile the menu appears at the bottom of the device and slides up from the bottom. On PC/Laptop the responsive menu slides in from the right. Submenu content is created on the fly and slides out from the right.

# Websites Portfolio

More details can be found in the [portfolio.md](https://github.com/bcpeterson7/project-samples/blob/main/portfolio.md) file

https://dp.dataportsis.com/  (K-12 School District Staff Portal Site)  
username: demo.account  
password: demo.account  

https://oregongutterservice.com/  
https://angelssitterservice.com/  
https://kofflerboats.com/  
https://kismetwebdesign.com/  
https://hawaiioceansports.com/  
https://emsrepair.com/  
