# Database Diagram for Gusturi Românești E-commerce Platform

Below is a textual representation of the database schema relationships. For a visual diagram, you would need to use a database modeling tool like MySQL Workbench, dbdiagram.io, or similar.

```
[utilizatori] 1──┐
                 │
                 ├──n [comenzi] 1──n [comenzi_produse] n──1 [produse] 1──┐
                 │                                                        │
                 ├──n [cos_cumparaturi] n──1 [produse] n──1 [categorii]   │
                 │                                                        │
                 ├──n [favorite] n──1 [produse] n──1 [regiuni]            │
                 │                                                        │
                 ├──n [puncte_fidelitate]                                 │
                 │                                                        │
                 ├──n [tranzactii_puncte]                                 │
                 │                                                        │
                 └──n [recenzii] n──1 [produse] n──n [etichete]           │
                                                                          │
                                                                          │
[informatii_nutritionale] 1──1 [produse] n──n [imagini_produse]           │
                                                                          │
                                                                          │
[vouchere] 1──n [comenzi]                                                 │
                                                                          │
                                                                          │
[jurnalizare] n──1 [utilizatori]                                          │
                                                                          │
                                                                          │
[istoric_preturi] n──1 [produse]                                          │
```

## Table Relationships

### User Management
- `utilizatori` has many `comenzi` (orders)
- `utilizatori` has many `cos_cumparaturi` (cart items)
- `utilizatori` has many `favorite` (wishlist items)
- `utilizatori` has one `puncte_fidelitate` (loyalty points)
- `utilizatori` has many `tranzactii_puncte` (loyalty transactions)
- `utilizatori` has many `recenzii` (reviews)

### Product Catalog
- `produse` belongs to one `categorii` (category)
- `produse` belongs to one `regiuni` (region)
- `produse` has many `produse_etichete` (product tags)
- `produse` has one `informatii_nutritionale` (nutritional info)
- `produse` has many `imagini_produse` (product images)

### Shopping & Orders
- `comenzi` belongs to one `utilizatori` (user)
- `comenzi` has many `comenzi_produse` (order items)
- `comenzi` may have one `vouchere` (voucher)
- `comenzi_produse` belongs to one `comenzi` (order)
- `comenzi_produse` belongs to one `produse` (product)
- `cos_cumparaturi` belongs to one `utilizatori` (user)
- `cos_cumparaturi` belongs to one `produse` (product)

### Customer Loyalty
- `puncte_fidelitate` belongs to one `utilizatori` (user)
- `tranzactii_puncte` belongs to one `utilizatori` (user)
- `tranzactii_puncte` may belong to one `comenzi` (order)

### Additional Features
- `recenzii` belongs to one `utilizatori` (user)
- `recenzii` belongs to one `produse` (product)
- `jurnalizare` may belong to one `utilizatori` (user)
- `istoric_preturi` belongs to one `produse` (product)

## Key Constraints

### Primary Keys
All tables have an auto-incrementing `id` as the primary key.

### Foreign Keys
- `produse.id_categorie` references `categorii.id`
- `produse.id_regiune` references `regiuni.id`
- `produse_etichete.id_produs` references `produse.id`
- `produse_etichete.id_eticheta` references `etichete.id`
- `informatii_nutritionale.id_produs` references `produse.id`
- `imagini_produse.id_produs` references `produse.id`
- `cos_cumparaturi.id_utilizator` references `utilizatori.id`
- `cos_cumparaturi.id_produs` references `produse.id`
- `favorite.id_utilizator` references `utilizatori.id`
- `favorite.id_produs` references `produse.id`
- `comenzi.id_utilizator` references `utilizatori.id`
- `comenzi.id_voucher` references `vouchere.id`
- `comenzi_produse.id_comanda` references `comenzi.id`
- `comenzi_produse.id_produs` references `produse.id`
- `puncte_fidelitate.id_utilizator` references `utilizatori.id`
- `tranzactii_puncte.id_utilizator` references `utilizatori.id`
- `tranzactii_puncte.id_comanda` references `comenzi.id`
- `recenzii.id_produs` references `produse.id`
- `recenzii.id_utilizator` references `utilizatori.id`
- `jurnalizare.id_utilizator` references `utilizatori.id`
- `istoric_preturi.id_produs` references `produse.id`
- `istoric_preturi.id_utilizator` references `utilizatori.id`

### Unique Constraints
- `utilizatori.email`
- `categorii.nume` and `categorii.slug`
- `regiuni.nume`
- `etichete.nume`
- `produse.cod_produs` and `produse.slug`
- `vouchere.cod`
- `comenzi.numar_comanda`
- `cos_cumparaturi` has a unique constraint on `(id_utilizator, id_produs)`
- `favorite` has a unique constraint on `(id_utilizator, id_produs)`
- `newsletter_abonati.email`
- `setari_site.cheie`

## Cascade Rules

- When a user is deleted:
  - Their cart items are deleted (CASCADE)
  - Their wishlist items are deleted (CASCADE)
  - Their loyalty points are deleted (CASCADE)
  - Their reviews are deleted (CASCADE)
  - Their orders are preserved (RESTRICT)

- When a product is deleted:
  - Its nutritional information is deleted (CASCADE)
  - Its images are deleted (CASCADE)
  - Its tags are deleted (CASCADE)
  - Its cart items are deleted (CASCADE)
  - Its wishlist items are deleted (CASCADE)
  - Its reviews are deleted (CASCADE)
  - Its price history is deleted (CASCADE)
  - Its order items are preserved (RESTRICT)

- When an order is deleted:
  - Its order items are deleted (CASCADE)

- When a voucher is deleted:
  - Orders referencing it will set id_voucher to NULL (SET NULL)