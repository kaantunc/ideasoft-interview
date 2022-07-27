<?php 
require 'db.php';

class Factory {

    // get products
    public static function login($customerId) {
        $db = new Db();
        $db = $db->connect();
        $prepare = $db->prepare("SELECT * FROM customers WHERE id = :customerId");
        $prepare->bindParam(":customerId", $customerId);
        $prepare->execute();
        $customer = $prepare->fetch(PDO::FETCH_OBJ);
        $db = null;

        if (empty($customer)) {
            throw new Exception('Kullanıcı yok.', 401);
        }

        return $customer;
    }

    // get products
    public static function getProducts() {
        $db = new Db();
        $db = $db->connect();
        $products = $db->query("SELECT * FROM products WHERE stock > 0")->fetchAll(PDO::FETCH_OBJ);
        $db = null;
        return $products;
    }

    // get product by id
    public static function getProduct($productId) {
        $db = new Db();
        $db = $db->connect();
        $prepare = $db->prepare("SELECT * FROM products WHERE id = :productId");
        $prepare->bindParam(":productId", $productId);
        $prepare->execute();
        $product = $prepare->fetch(PDO::FETCH_OBJ);
        $db = null;

        return $product;
    }

    // get waiting order
    public static function getWaitingOrder($customerId) {
        $db = new Db();
        $db = $db->connect();
        $prepare = $db->prepare("SELECT * FROM orders WHERE customerId = :customerId AND status = 0");
        $prepare->bindParam(":customerId", $customerId);
        $prepare->execute();
        $order = $prepare->fetch(PDO::FETCH_OBJ);
        $db = null;

        return $order;
    }

    // update order price and discounts
    public static function updateOrder($orderId) {
        $db = new Db();
        $db = $db->connect();

        $prepare = $db->prepare("SELECT SUM(total) as orderTotal FROM order_products WHERE orderId = :orderId");
        $prepare->bindParam(":orderId", $orderId);
        $prepare->execute();
        $orderTotal = $prepare->fetch(PDO::FETCH_OBJ);

        if (empty($orderTotal)) {
            self::deleteOrder($orderId);
            $db = null;
            return true;
        }

        $discountTotal = 0;
        $productsInOrder = self::getOrderProducts($orderId);
        $discountArray = [];
        foreach ($productsInOrder as $row) {
            $prepare = $db->prepare("SELECT * FROM discount_ WHERE status = 'ACTIVE' AND conditionType = 'product' AND ( category = :category OR productId = :productId)");
            $prepare->bindParam(":category", $row->category);
            $prepare->bindParam(":productId", $row->productId);
            $prepare->execute();
            $discounts = $prepare->fetchAll(PDO::FETCH_OBJ);
            
            $maxDiscount = 0;
            $discountArr = [];
            foreach ($discounts as $discount) {
                if (($discount->min_order_type == 'product' && $row->quantity >= $discount->min_order_value)
                    || 
                    ($discount->min_order_type == 'money' && $row->total >= $discount->min_order_value)) 
                {
                    if ($discount->discountType == 'free' && $maxDiscount < $row->unitPrice * $discount->discount) {
                        $maxDiscount = $row->unitPrice * $discount->discount;
                        $discountArr = [
                            'discountId' => $discount->id,
                            'discountReason' => $discount->name,
                            'discount' => $maxDiscount
                        ];
                    } else if ($discount->discountType == 'money' && $maxDiscount < $discount->discount) {
                        $maxDiscount = $discount->discount;
                        $discountArr = [
                            'discountId' => $discount->id,
                            'discountReason' => $discount->name,
                            'discount' => $maxDiscount
                        ];
                    } else if ($discount->discountType == 'percentage' && $maxDiscount < (($row->total * $discount->discount) / 100)) {
                        $maxDiscount = ($row->total * $discount->discount) / 100;
                        $discountArr = [
                            'discountId' => $discount->id,
                            'discountReason' => $discount->name,
                            'discount' => $maxDiscount
                        ];
                    }
                }
            }
            
            if (!empty($discountArr)) {
                $discountArray[] = $discountArr;
            }
        }

        // discount by category
        $prepare = $db->prepare("SELECT * FROM discount_ WHERE status = 'ACTIVE' AND conditionType = 'category'");
        $prepare->execute();
        $discounts = $prepare->fetchAll(PDO::FETCH_OBJ);

        foreach ($discounts as $discount) {
            $sql = "SELECT * FROM (SELECT SUM(quantity) as quantity, SUM(total) as total FROM order_products WHERE productId IN (SELECT id FROM products WHERE category = :category)) as disord";
            if ($discount->min_order_type == 'product') {
                $sql .= " WHERE quantity >= " . $discount->min_order_value;
            } else if ($discount->min_order_type == 'money') {
                $sql .= " WHERE total >= " . $discount->min_order_value;
            }
            $prepare = $db->prepare($sql);
            $prepare->bindParam(":category", $discount->category);
            $prepare->execute();
            if ($prepare->fetch(PDO::FETCH_OBJ)) {
                $sql = "SELECT * FROM order_products WHERE productId IN (SELECT id FROM products WHERE category = :category) ORDER BY unitPrice";
                $prepare = $db->prepare($sql);
                $prepare->bindParam(":category", $discount->category);
                $prepare->execute();
                $products = $prepare->fetchAll(PDO::FETCH_OBJ);

                if ($discount->discountType == 'free') {
                    $discountArray[] = [
                        'discountId' => $discount->id,
                        'discountReason' => $discount->name,
                        'discount' => $products[0]->unitPrice * $discount->discount
                    ];
                } else if ($discount->discountType == 'money') {
                    $discountArray[] = [
                        'discountId' => $discount->id,
                        'discountReason' => $discount->name,
                        'discount' => $products[0]->discount
                    ];
                } else if ($discount->discountType == 'percentage') {
                    $discountArray[] = [
                        'discountId' => $discount->id,
                        'discountReason' => $discount->name,
                        'discount' => ($products[0]->unitPrice * $discount->discount) / 100
                    ];
                }
            }
        }

        // discount by total
        array_map(function($val) use (&$discountTotal) {
            $discountTotal += $val['discount'];
        }, $discountArray);

        $total = $orderTotal->orderTotal - $discountTotal;

        $prepare = $db->prepare("SELECT * FROM discount_ WHERE status = 'ACTIVE' AND conditionType = 'total' and min_order_value <= :total");
        $prepare->bindParam(":total", $total);
        $prepare->execute();
        $discounts = $prepare->fetchAll(PDO::FETCH_OBJ);

        $maxDiscount = 0;
        $discountArr = [];
        foreach ($discounts as $discount) {
            if ($discount->discountType == 'money' && $maxDiscount < $discount->discount) {
                $maxDiscount = $discount->discount;
                $discountArr = [
                    'discountId' => $discount->id,
                    'discountReason' => $discount->name,
                    'discount' => $maxDiscount
                ];
            } else if ($discount->discountType == 'percentage' && $maxDiscount < (($total * $discount->discount) / 100)) {
                $maxDiscount = ($total * $discount->discount) / 100;
                $discountArr = [
                    'discountId' => $discount->id,
                    'discountReason' => $discount->name,
                    'discount' => $maxDiscount
                ];
            }
        }

        if (!empty($discountArr)) {
            $discountArray[] = $discountArr;
        }

        $discountTotal = 0;
        $total = $orderTotal->orderTotal;
        foreach ($discountArray as $key => $val) {
            $total -= $val['discount'];
            $discountTotal += $val['discount'];
            $discountArray[$key]['subtotal'] = number_format($total, 2, '.', '');
            $discountArray[$key]['discountAmount'] = $val['discount'];
            unset($discountArray[$key]['discount']);
        }

        $prepare = $db->prepare("UPDATE orders SET order_total = :order_total, discount = :discount, total = :total, discounts = :discounts WHERE id = :orderId");
        $prepare->bindParam(":orderId", $orderId);
        $prepare->bindParam(":total", $total);
        $prepare->bindParam(":discount", $discountTotal);
        $prepare->bindParam(":order_total", $orderTotal->orderTotal);
        $discountJSON = json_encode($discountArray);
        $prepare->bindParam(":discounts", $discountJSON);
        $prepare->execute();

        $db = null;
        return true;
    }

    // delete products in order list
    public static function deleteOrderProduct($productId, $customerId) {
        $order = self::getWaitingOrder($customerId);
        if ($productId === 'all') {
            $result = self::deleteOrder($order->id);
        } else {
            $result = self::deleteProduct($productId, $order->id);
            self::updateOrder($order->id);
        }

        return $result;
    }

    // delete product
    public static function deleteProduct($productId, $orderId) {
        $db = new Db();
        $db = $db->connect();
        $prepare = $db->prepare("DELETE FROM order_products WHERE orderId = :orderId AND productId = :productId");
        $prepare->bindParam(":orderId", $orderId);
        $prepare->bindParam(":productId", $productId);
        $result = $prepare->execute();
        $db = null;

        if (!$result) {
            throw new Exception('Silme işlemi sırasında bir problem oluştu.', 500);
        }

        return true;
    }

    // delete order
    public static function deleteOrder($orderId) {
        $db = new Db();
        $db = $db->connect();
        $prepare = $db->prepare("DELETE FROM order_products WHERE orderId = :orderId");
        $prepare->bindParam(":orderId", $orderId);
        $result = $prepare->execute();

        if (!$result) {
            throw new Exception('Silme işlemi sırasında bir problem oluştu.', 500);
        }

        $prepare = $db->prepare("DELETE FROM orders WHERE id = :orderId");
        $prepare->bindParam(":orderId", $orderId);
        $result = $prepare->execute();

        if (!$result) {
            throw new Exception('Silme işlemi sırasında bir problem oluştu.', 500);
        }

        return true;
    }

    // create waiting order
    public static function createWaitingOrder($customerId) {
        $db = new Db();
        $db = $db->connect();
        $prepare = $db->prepare("INSERT INTO orders (customerId) VALUES (:customerId)");
        $prepare->bindParam(":customerId", $customerId);
        $prepare->execute();

        return self::getWaitingOrder($customerId);
    }

    // check product stock
    public static function checkStock($productId, $quantity) {
        $product = self::getProduct($productId);
        if (empty($product)) {
            throw new Exception('Ürün bulunamadı.', 404);
        }

        if ($product->stock <= 0) {
            throw new Exception('Ürün stoklarda yok.', 404);
        }

        if ($product->stock < $quantity) {
            throw new Exception('Stok aşıldı.', 404);
        }

        return true;
    }

    // add product to order
    public static function addProductToOrder($productId, $quantity, $customerId) {
        $checkStock = self::checkStock($productId, $quantity);

        $order = self::getWaitingOrder($customerId);
        if (empty($order)) {
            $order = self::createWaitingOrder($customerId);
        }
        $orderId = $order->id;

        $db = new Db();
        $db = $db->connect();
        // check if the product exists in order list
        $prepare = $db->prepare("SELECT * FROM order_products WHERE orderId = :orderId AND productId = :productId");
        $prepare->bindParam(":orderId", $orderId);
        $prepare->bindParam(":productId", $productId);
        $prepare->execute();
        $productInOrder = $prepare->fetch(PDO::FETCH_OBJ);

        if ($productInOrder) {
            $prepare = $db->prepare("DELETE FROM order_products WHERE orderId = :orderId AND productId = :productId");
            $prepare->bindParam(":orderId", $orderId);
            $prepare->bindParam(":productId", $productId);
            $prepare->execute();
        }

        $db = null;

        self::addProductToOrderList($productId, $quantity, $orderId);
        self::updateOrder($orderId);
        return true;
    }

    // add product to order list
    public static function addProductToOrderList($productId, $quantity, $orderId) {
        $product = self::getProduct($productId);

        $db = new Db();
        $db = $db->connect();
        // add product to order list
        $prepare = $db->prepare("INSERT INTO order_products (orderId, productId, quantity, unitPrice, total) VALUES (:orderId, :productId, :quantity, :unitPrice, :total)");
        $prepare->bindParam(":orderId", $orderId);
        $prepare->bindParam(":productId", $productId);
        $prepare->bindParam(":quantity", $quantity);
        $prepare->bindParam(":unitPrice", $product->price);
        $total = number_format(($quantity * $product->price), 2, '.', '');
        $prepare->bindParam(":total", $total);
        $insertProduct = $prepare->execute();
        $db = null;
        return $insertProduct;
    }

    // get order list
    public static function getOrderList($customerId) {
        $order = self::getWaitingOrder($customerId);
        if (empty($order)) {
            throw new Exception('Listenizde henüz hiç bir ürün yoktur.', 404);
        }

        $db = new Db();
        $db = $db->connect();
        $prepare = $db->prepare("SELECT DISTINCT op.productId, op.quantity, op.unitPrice, op.total, pr.name, pr.category FROM order_products op INNER JOIN products pr ON op.productId = pr.id WHERE op.orderId = :orderId");
        $prepare->bindParam(":orderId", $order->id);
        $prepare->execute();
        $productsInOrder = $prepare->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        $result = [
            'id' => $order->id,
            'customerId' => $customerId,
            'items' => $productsInOrder,
            'total_product' => $order->order_total,
            'discount' => $order->discount,
            'total' => $order->total,
            'discounts' => json_decode($order->discounts, true)
        ];

        return $result;
    }

    // get order products
    public static function getOrderProducts($orderId) {
        $db = new Db();
        $db = $db->connect();
        $prepare = $db->prepare("SELECT DISTINCT op.productId, op.quantity, op.unitPrice, op.total, pr.name, pr.category FROM order_products op INNER JOIN products pr ON op.productId = pr.id WHERE op.orderId = :orderId");
        $prepare->bindParam(":orderId", $orderId);
        $prepare->execute();
        $productsInOrder = $prepare->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        return $productsInOrder;
    }

    // get discounts in db
    public static function getDiscounts() {
        $db = new Db();
        $db = $db->connect();
        $prepare = $db->prepare("SELECT * FROM discount_ WHERE status = 'ACTIVE' AND conditionType = 'product'");
        $prepare->execute();
        $productDiscounts = $prepare->fetchAll(PDO::FETCH_OBJ);

        $prepare = $db->prepare("SELECT * FROM discount_ WHERE status = 'ACTIVE' AND conditionType = 'category'");
        $prepare->execute();
        $categoryDiscounts = $prepare->fetchAll(PDO::FETCH_OBJ);

        $prepare = $db->prepare("SELECT * FROM discount_ WHERE status = 'ACTIVE' AND conditionType = 'total'");
        $prepare->execute();
        $totalDiscounts = $prepare->fetchAll(PDO::FETCH_OBJ);
        $db = null;

        $result = [
            'productDiscounts' => $productDiscounts,
            'categoryDiscounts' => $categoryDiscounts,
            'totalDiscounts' => $totalDiscounts
        ];

        return $result;
    }

    // get order
    public static function getOrder($orderId) {
        $db = new Db();
        $db = $db->connect();
        $prepare = $db->prepare("SELECT * FROM orders WHERE id = :orderId");
        $prepare->bindParam(":orderId", $orderId);
        $prepare->execute();
        $order = $prepare->fetch(PDO::FETCH_OBJ);

        $db = null;

        return $order;
    }

    // get order discounts list
    public static function getOrderDiscounts($orderId, $customerId) {
        $order = self::getOrder($orderId);

        if (empty($order) || $order->customerId != $customerId) {
            throw new Exception('Sipariş bulunamadı.', 404);
        }

        return [
            'orderId' => $order->id,
            'discounts' => json_decode($order->discounts, true),
            'totalDiscount' => $order->discount,
            'discountedTotal' => $order->total
        ];
    }

    // complete order
    public static function completeOrder($orderId, $customerId) {
        $order = self::getOrder($orderId);

        if (empty($order) || $order->customerId != $customerId || $order->status == '-1') {
            throw new Exception('Sipariş bulunamadı.', 404);
        } else if ($order->status == '1') {
            return true;
        } else {
            $db = new Db();
            $db = $db->connect();

            // calculate stock
            $orderProducts = self::getOrderProducts($orderId);
            foreach ($orderProducts as $row) {
                $product = self::getProduct($row->productId);

                $stock = $product->stock - $row->quantity;

                $sql = "UPDATE products SET stock = $stock WHERE id = $row->productId";
                $prepare = $db->prepare($sql);
                $prepare->execute();
            }

            $prepare = $db->prepare("UPDATE orders SET status = 1 WHERE id = :orderId");
            $prepare->bindParam(":orderId", $orderId);
            $result = $prepare->execute();
            
            $db = null;
            return $result;
        }
    }
}