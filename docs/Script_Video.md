# Midnight Market demo video script

The video runs about 4 minutes. Lines in quote blocks are what you say. Lines in italics tell you what to show on screen.

## Before you record

- Run `php phantom migrate:fresh --seed` so the shop starts with clean demo data.
- Start the app with `.\start`.
- Put at least one NVIDIA API key in `.env`. Rook still works without one (offline mode), but its replies are simpler.
- Keep both demo logins handy: `ana@midnight.test` / `shopper123` and `admin@midnight.test` / `phantom-admin`.
- Start logged out, with an empty bag.
- Rook takes a few seconds to answer. Cut the waiting when you edit.

## 1. Opening (about 20 seconds)

*Show the home page with the "Steal the Night" headline.*

> Hi! This is Midnight Market, our final project for IPT10. It's an online shop for night owls, styled like a phantom-thief caper. The whole thing runs on Phantom, a PHP framework we wrote from scratch. We didn't use Composer or any outside packages.

## 2. Browsing the shop (about 30 seconds)

*Scroll past the categories and the Featured loot section. Click "Enter the shop", search for "coffee", pick a category and sort by price.*

> This is the catalog. You can search, filter by category or price, and sort the results.

*Open any product.*

> Every product gets its own poster-style artwork, which the app draws as an SVG. Below it you'll see reviews and related items.

*Switch the currency at the top from PHP to USD.*

> Prices are in pesos, but you can switch currencies. The exchange rates come live from the Frankfurter API, and we save them in the database so we don't call the API on every page.

## 3. Asking Rook for help (about 45 seconds)

*Go back to the home page. Under "Meet Rook", click "Try that request".*

> This is Rook, our AI shopping assistant. It runs on NVIDIA Nemotron. I'm asking it for a warm coat and coffee beans, under 6,000 pesos total.

*Wait for the reply.*

> Rook searched the catalog by itself and picked items that fit the budget.

*Type "Add both to my bag" and send it.*

> Now I'll ask it to add them. And there they are, in the bag. Rook can fill your bag, but it can't check out or pay. You always do that part yourself.

## 4. Checking out (about 40 seconds)

*Open Your bag and click checkout. Log in as Ana when the site asks.*

> I was shopping as a guest. When I log in, my bag moves over to my account.

*Fill in the drop-off details, choose cash on delivery and place the order.*

> Checkout runs as one database transaction. It locks the products, checks the stock and saves the order together, so two people can't buy the last item at the same time. Payments are simulated since this is a demo.

*Show the calling card page.*

> This is the calling card, our order confirmation. It has the order reference.

## 5. Tracking the order (about 20 seconds)

*Open My Heists and click the new order.*

> My Heists is the order history. Each order has a status timeline, and while it's still pending you can cancel it. The items go straight back into stock.

*Open Rook and click "Track my latest order".*

> Rook can look up orders too, but only your own. It checks who's logged in before it shows anything.

## 6. The admin side (about 45 seconds)

*Log out and log in as the admin. Show the dashboard.*

> Now the admin side. The dashboard shows 30-day revenue, a sales chart, the latest orders and products that are running low.

*Open Orders, open Ana's order and set the status to Shipped.*

> Admins manage products, categories, orders and customers. I'll mark Ana's order as shipped.

*Open Products, edit any product and click "Write with AI".*

> There's also an AI copywriter. One click and it drafts a product description.

## 7. Rook Intel (about 30 seconds)

*Open the Intel page. Click "Give me a 30-day sales briefing", then "What should I restock first?"*

> Admins get their own assistant called Rook Intel. It writes sales briefings and tells you what's running low. It can prepare a restock or a status change, but an admin has to apply it on the product or order page. We did that on purpose, so nobody can trick the AI into changing our data.

*Scroll down to the Agent activity log.*

> Every tool the AI uses is logged here, with what went in and what came out.

## 8. Under the hood (about 25 seconds)

*Switch to VS Code and open the `framework` folder. Then run `php phantom test` in the terminal.*

> A quick look at the code. This folder is our framework: the router, the dependency injection container, the query builder, templates, validation and sessions. We wrote our own test runner too, and here are the tests passing.

> For security, we used CSRF tokens, prepared statements, hashed passwords and rate limits on login and chat.

## 9. Closing (about 10 seconds)

*Go back to the home page.*

> That's Midnight Market. Thanks for watching!
