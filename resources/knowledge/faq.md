# Midnight Market help center

<!--
Rook's knowledge base. Rook searches it with the search_knowledge_base tool and answers
questions about the shop, itself and store policies only from these entries.

Format: each entry starts with "## Title", may have a "keywords:" line, then the answer.
Placeholders in {braces} are filled at runtime, so facts always match the live app:
  from config:   {shipping_fee} {free_shipping_over} {max_qty} {currencies} {payment_methods}
                 {statuses} {rate_refresh_hours} {low_stock} {model}
  from database: {catalog_overview} {product_count} {price_min} {price_max}
Every statement here describes how the web app really behaves. Keep it that way: if a
feature changes, update the entry in the same commit. Do not add facts the app does not
have (addresses, phone numbers, founding dates) unless they are real.
-->

## About Midnight Market
keywords: what is this shop, what is midnight market, company, store, brand, business, about us, night owls, philippines, where are you located, location, based, address, physical store, open
Midnight Market is an online shop for night owls, planners and "gentle thieves of the spotlight": people who do their best work when the city sleeps. It curates gear for late nights: masks and accessories, outerwear, gadgets, books and journals, and coffee and snacks. It is an online shop based in the Philippines: prices are in Philippine pesos, you can pay by cash on delivery or GCash, and it ships nationwide. It sells online only, so there is no shop address to visit. Its motto is "Open after dark", but the online store never closes, so you can browse and order any time. Midnight Market is a student final project for IPT10 (Integrative Programming and Technologies), so it is a working demo with simulated payments.

## What Midnight Market sells
keywords: products, catalog, categories, what do you sell, range, items, merchandise, collection, price range, gift ideas
The catalog has {product_count} products, priced from {price_min} to {price_max}, in these categories:
{catalog_overview}
The home page shows "Featured loot" and "Fresh arrivals". Every product page suggests related items under "Partners in crime". Rook can search by need or budget, for example "a gift under ₱1,000" or "something warm for night walks".

## The Midnight Market style and story
keywords: theme, style, design, thief, phantom, why, aesthetic, steal the night, brand story, red black, why the thief theme
The shop is styled like a phantom-thief caper: bold red, black and white, slanted panels, halftone textures and ransom-note headings. The home page headline is "Steal the Night", and its calling card reads "Your style will be taken." After every order you get a calling card of your own: "Your haul is locked in. We'll slip it to your door soon." Each product has its own generated poster-style artwork. The heist talk is only style; everything is bought and paid for normally.

## Site guide and shop vocabulary
keywords: hideout, my heists, heist, haul, crew, codename, vault, drop-off, word on the street, partners in crime, calling card, glossary, pages, menu, navigation, what is a heist, what does calling card mean
- Hideout: the home page.
- Shop: the full catalog, with search and filters. "The vault" is the catalog when you search it.
- Your bag: your shopping cart.
- My Heists: your order history and order tracking (/orders). A heist is an order; your haul is the items in it.
- Calling card: the confirmation page you see after placing an order.
- Drop-off: your delivery details at checkout.
- Join the crew: create an account. A codename is an optional display name you can choose.
- Word on the street: product reviews. Partners in crime: related products.
- Featured loot and Fresh arrivals: featured and newest products on the home page.
- Ask Rook: the chat button on every page.

## Shipping fees and free shipping
keywords: shipping, delivery fee, deliver, courier, free shipping, how much is shipping, shipping cost
Shipping is a flat {shipping_fee} per order. It is free when your cart subtotal reaches {free_shipping_over} or more. The fee is added at checkout and shown in your bag before you pay, along with how much more you need for free shipping.

## Delivery time and tracking
keywords: how long, when will it arrive, delivery time, track, tracking, where is my order, eta
The shop does not promise a fixed delivery date. You can follow your order on the My Heists page (/orders): open an order to see its status timeline, from pending to processing, shipped and delivered. Rook can also look up the status of your own orders when you are logged in; just give it the order reference.

## Payment methods
keywords: payment, pay, gcash, card, credit card, cash on delivery, cod, how to pay, charged, real money
You choose how to pay at checkout: {payment_methods}. This is a demo store, so GCash and card payments are simulated and no real charge is made. You never enter card numbers on the site. Rook cannot take payments or place orders for you; you always confirm checkout yourself.

## Currencies and exchange rates
keywords: currency, dollars, usd, euro, yen, convert, exchange rate, peso, foreign, price in, pay in dollars, pay in another currency
Prices are set in Philippine pesos (PHP). You can browse in {currencies} using the currency switcher at the top of the page. Conversions use live European Central Bank rates from the Frankfurter API, refreshed about every {rate_refresh_hours} hours. If the rate service is unreachable, the last saved or approximate rates are used. Checkout is always charged in PHP, and your order page records the currency and rate you were browsing with at the time.

## Shopping cart (your bag)
keywords: cart, bag, add to cart, quantity, limit, maximum, how many, remove item, update quantity, guest
You do not need an account to fill your bag. Each product can be added up to {max_qty} at a time, and never more than the stock available. Change quantities or remove items on the Your bag page. If you shop as a guest and then log in or register, your guest bag is merged into your account. Sold-out items cannot be added.

## Stock and sold-out items
keywords: out of stock, sold out, restock, available, in stock, only left, low stock, back in stock
Each product page shows whether it is in stock. When {low_stock} or fewer units remain, it shows "Only N left". Sold-out items cannot be added to the bag. Stock is checked again when you place your order, so if someone buys the last unit first, checkout tells you. Restocking is done by the shop team; the site has no back-in-stock alerts yet.

## How to check out
keywords: checkout, place order, buy, order, how to order, address, phone, delivery details, notes
Checkout needs an account, so log in or register first. At checkout you enter your full name, phone number, delivery address and city, plus optional notes for the courier (up to 500 characters), then choose a payment method. Prices and stock are confirmed when you place the order, and the prices are locked into it. You then see a "calling card" confirmation page with your order reference.

## Order reference numbers
keywords: reference, order number, order id, MM-, confirmation, calling card
Every order gets a reference like MM-261119-AB3CD: "MM", the date the order was placed (YYMMDD) and a five-character code. You can find it on the confirmation page and on My Heists (/orders). Give this reference to Rook to check an order's status.

## Order statuses
keywords: status, pending, processing, shipped, delivered, cancelled, what does status mean
Orders move through these statuses: {statuses}. Pending means the order was placed and is waiting to be prepared. Processing means the shop team is preparing it. Shipped means it is on the way. Delivered means it arrived. Cancelled means the order was stopped and its items went back into stock. A cancelled order cannot be reopened.

## Cancelling an order
keywords: cancel, cancel order, change my mind, stop order, undo order
You can cancel an order yourself while it is still pending: open it on My Heists and click Cancel order. The items go back into stock straight away. Once an order is processing or shipped, you cannot cancel it on the site; ask the shop team. Rook cannot cancel orders for you.

## Returns and refunds
keywords: return, refund, money back, exchange, damaged, wrong item, warranty
The site has no online returns or refunds. Because GCash and card payments are simulated, no real money is charged. If you change your mind, cancel the order while it is still pending. For a problem with a delivered order, contact the shop team.

## Changing an order or delivery address
keywords: change address, edit order, modify order, wrong address, add item to order
Orders cannot be edited after they are placed, and Rook cannot change addresses. If the order is still pending, cancel it and place a new one with the correct details.

## Creating an account and logging in
keywords: register, sign up, account, login, log in, codename, password, create account, logout
Register with your full name, an optional codename (shown as your display name), your email and a password of 8 to 72 characters. You need an account to check out, see your order history, write reviews and let Rook look up your orders. Log out from the menu at any time.

## Forgot password
keywords: forgot password, reset password, change password, locked out, can't log in, cannot login
The demo store has no self-service password reset. Ask the shop admin for help. Login attempts are rate-limited, so wait a minute after several failed tries.

## Privacy and security
keywords: privacy, data, secure, safe, personal information, who can see my orders, security
Only you can see your orders. Rook can only see your own bag and, when you are logged in, your own orders, never anyone else's. Passwords are stored as one-way bcrypt hashes, and the site uses security tokens on every form. No card details are collected because payments are simulated.

## Reviews and ratings
keywords: review, rating, stars, comment, feedback, write a review
Logged-in shoppers can review a product once: a rating of 1 to 5 stars and a comment of 5 to 1000 characters. Reviews and the average rating appear on the product page under "Word on the street". Reviews from buyers carry the most weight.

## Searching and browsing the catalog
keywords: search, find, filter, price range, sort, cheapest, newest, browse
On the Shop page you can search by keyword, filter by category, set a minimum and maximum price, show only in-stock items, and sort by featured, newest, price (low to high or high to low) or name. Results show 12 per page. Each product page also suggests related items under "Partners in crime". Rook can run these searches for you.

## Who is Rook?
keywords: rook, who are you, your name, assistant, ai, chatbot, bot, concierge, are you human, are you real, model, nemotron, nvidia
I'm Rook, Midnight Market's AI concierge. I live behind the "Ask Rook" button on every page, and my style is confident and a little theatrical, like a gentleman thief's calling card, while keeping answers short and clear. I'm an agentic assistant: instead of only chatting, I use the shop's own tools to search the catalog, check stock, convert prices, fill your bag, track your orders and look things up in this help center. I run on NVIDIA's {model} model through the NVIDIA NIM API. I'm an AI, not a person, and I can make mistakes, so check your bag before you check out. If the AI service isn't available, I switch to a simpler offline mode that understands common requests.

## What Rook can and cannot do
keywords: what can you do, help, capabilities, abilities, limits, can rook, features
Rook can search the catalog, show product details, list categories, add items to your bag or change quantities, show your bag, convert prices into other currencies, look up your own orders when you are logged in, and answer questions from this help center. It cannot place orders, take payments, cancel orders, issue refunds or change addresses. You always review your bag and check out yourself.

## How Rook handles your chats
keywords: chat history, memory, remember, conversation, saved, logged, delete chat, reset, new chat, privacy, data, message limit
Rook remembers your current conversation so you can ask follow-up questions. The conversation is saved in the shop's database and linked to your account, or to your browser session if you are a guest. The ↺ button in the chat window deletes it and starts fresh, and very long conversations restart on their own. Every action Rook takes (searches, cart changes, order lookups) is recorded in an activity log that the shop's admins can review. Rook never sees passwords or payment details. Messages can be up to 1000 characters, and you can send about 15 per minute.

## Rook Intel, the admin assistant
keywords: rook intel, admin, analyst, staff, shop team, reports, briefing, management
Rook Intel is a separate assistant for the shop's admins, on the admin Intel page. It writes sales briefings and answers questions about best-sellers, stock, categories, customers and recent orders. It can prepare an order-status change or a restock, but it cannot apply changes itself: an admin applies them on the order or product page. Shoppers cannot use Rook Intel.

## Is Midnight Market a real store?
keywords: real, fake, demo, legit, legitimate, scam, school project, test, sample, actual store
Midnight Market is a working demo built as a school final project. You can browse, fill a bag and place orders, but GCash and card payments are simulated, so no real money is charged and you never enter card details. The products, reviews and sample customer accounts are demo data.

## Who built Midnight Market?
keywords: who made, who built, developers, team, students, creators, ipt10, project, technology, how was it made, framework, phantom
Midnight Market was built by students as their final project for IPT10, Integrative Programming and Technologies. It runs on Phantom, a PHP web framework the team wrote from scratch without third-party packages, with a MySQL database. Rook runs on NVIDIA NIM ({model}), and live exchange rates come from the free Frankfurter API (European Central Bank data).
