<? if(!$product): ?>
	<div class="col-12">
  	<h2>We are sorry, that product was not found.</h2>
  </div>
<? return ?>
<? elseif($product_unavailable): ?>
	<div class="col-12">
  	<h2>We are sorry, that product is unavailable.</h2>
  </div>
<? return ?>
<? endif ?>
<div id="product-page">
<? $this->render_partial('shop:product') ?>
</div>