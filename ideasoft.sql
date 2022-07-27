/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50731
 Source Host           : localhost:3306
 Source Schema         : ideasoft

 Target Server Type    : MySQL
 Target Server Version : 50731
 File Encoding         : 65001

 Date: 27/07/2022 13:10:38
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for customers
-- ----------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `since` datetime(0) DEFAULT NULL,
  `revenue` double DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of customers
-- ----------------------------
INSERT INTO `customers` VALUES (1, 'Türker Jöntürk', NULL, 492.12);
INSERT INTO `customers` VALUES (2, 'Kaptan Devopuz', NULL, 1505.95);
INSERT INTO `customers` VALUES (3, 'İsa Sonuyumaz', NULL, 0);

-- ----------------------------
-- Table structure for discount_
-- ----------------------------
DROP TABLE IF EXISTS `discount_`;
CREATE TABLE `discount_`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `conditionType` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `min_order_value` int(11) DEFAULT NULL,
  `min_order_type` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `discountType` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `discount` int(11) DEFAULT NULL,
  `category` int(11) DEFAULT NULL,
  `productId` int(11) DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 4 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of discount_
-- ----------------------------
INSERT INTO `discount_` VALUES (1, '10_PERCENT_OVER_1000', 'total', 1000, 'money', 'percentage', 10, NULL, NULL, 'ACTIVE');
INSERT INTO `discount_` VALUES (2, 'BUY_5_GET_1', 'product', 6, 'product', 'free', 1, 2, NULL, 'ACTIVE');
INSERT INTO `discount_` VALUES (3, '20_PERCENT_MIN_ORDER', 'category', 2, 'product', 'percentage', 20, 1, NULL, 'ACTIVE');

-- ----------------------------
-- Table structure for order_products
-- ----------------------------
DROP TABLE IF EXISTS `order_products`;
CREATE TABLE `order_products`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderId` int(11) NOT NULL,
  `productId` int(11) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unitPrice` double DEFAULT 0,
  `total` double DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 76 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Fixed;

-- ----------------------------
-- Records of order_products
-- ----------------------------
INSERT INTO `order_products` VALUES (18, 4, 1, 7, 120.75, 845.25);
INSERT INTO `order_products` VALUES (38, 4, 5, 6, 12.95, 77.7);
INSERT INTO `order_products` VALUES (75, 4, 2, 6, 49.5, 297);

-- ----------------------------
-- Table structure for orders
-- ----------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerId` int(11) NOT NULL,
  `order_total` double DEFAULT NULL,
  `discount` double DEFAULT 0,
  `total` double DEFAULT 0,
  `discounts` text CHARACTER SET utf8 COLLATE utf8_general_ci,
  `status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '-1 => Canceled #\r\n0 => preorder # \r\n1 => Ordered #',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 5 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of orders
-- ----------------------------
INSERT INTO `orders` VALUES (1, 2, NULL, 0, 0, NULL, 0);
INSERT INTO `orders` VALUES (4, 1, 1219.95, 142.56, 1077.39, '[{\"discountId\":\"2\",\"discountReason\":\"BUY_5_GET_1\",\"subtotal\":\"1207.00\",\"discountAmount\":12.95},{\"discountId\":\"3\",\"discountReason\":\"20_PERCENT_MIN_ORDER\",\"subtotal\":\"1197.10\",\"discountAmount\":9.9},{\"discountId\":\"1\",\"discountReason\":\"10_PERCENT_OVER_1000\",\"subtotal\":\"1077.39\",\"discountAmount\":119.71}]', 0);

-- ----------------------------
-- Table structure for products
-- ----------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `category` int(11) NOT NULL,
  `price` double NOT NULL,
  `stock` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 6 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of products
-- ----------------------------
INSERT INTO `products` VALUES (1, 'Black&Decker A7062 40 Parça Cırcırlı Tornavida Seti', 1, 120.75, 10);
INSERT INTO `products` VALUES (2, 'Reko Mini Tamir Hassas Tornavida Seti 32\'li', 1, 49.5, 10);
INSERT INTO `products` VALUES (3, 'Viko Karre Anahtar - Beyaz', 2, 11.28, 10);
INSERT INTO `products` VALUES (4, 'Legrand Salbei Anahtar, Alüminyum', 2, 22.8, 10);
INSERT INTO `products` VALUES (5, 'Schneider Asfora Beyaz Komütatör', 2, 12.95, 10);

SET FOREIGN_KEY_CHECKS = 1;
