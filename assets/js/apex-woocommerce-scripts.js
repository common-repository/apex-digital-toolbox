function apex_viewShippingDelivery() {
	jQuery("#tab-title-shipping_tab a").trigger("click");
	jQuery("html,body").animate({scrollTop: jQuery(".woocommerce-tabs").offset().top -jQuery(".mkhb-sticky:visible").height()-15}, 500);
}