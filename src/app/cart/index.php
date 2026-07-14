<?php require './models/Icon.php'; ?>
<div class="page">
    <div class="page-header">
        <div>
            <h1>My Cart</h1>
        </div>
    </div>
    <div class="main">
		<div class="cart">
			<div class="game-card">
				<div class="game-pic">
					<img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ9beYfu27OQsFfv8OEM9zxiBq9vw5Z8wcDM3_hfs8syA&s=10" alt="Game Cover">
				</div>
				<div class="game-info">
					<h3>Game 1</h3>
					<div class="game-genre">
						<span class="magenta game-tag">RPG</span>
					</div>
					<div class="game-platform">
						<?= Icon::get('windows', 20) ?>
						<?= Icon::get('apple', 20) ?>
					</div>
				</div>
				<div class="game-price">
					<span class="discount">-50%</span>
					<span class="original">RM52.00</span>
					<span class="current">RM26.00</span>
				</div>
				<div class="actions">
					<button class="remove-btn">Remove</button>
					<button class="wishlist-btn">Move to wishlist</button>
				</div>
			</div>

			<div class="game-card">
				<div class="game-pic">
					<img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ9beYfu27OQsFfv8OEM9zxiBq9vw5Z8wcDM3_hfs8syA&s=10" alt="Game Cover">
				</div>
				<div class="game-info">
					<h3>Game 2</h3>
					<div class="game-genre">
						<span class="magenta game-tag">RPG</span>
					</div>
					<div class="game-platform">
						<?= Icon::get('windows', 20) ?>
						<?= Icon::get('apple', 20) ?>
					</div>
				</div>
				<div class="game-price">
					<span class="current">RM152.00</span>
				</div>
				<div class="actions">
					<button class="remove-btn">Remove</button>
					<button class="wishlist-btn">Move to wishlist</button>
				</div>
			</div>

			<div class="game-card">
				<div class="game-pic">
					<img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ9beYfu27OQsFfv8OEM9zxiBq9vw5Z8wcDM3_hfs8syA&s=10" alt="Game Cover">
				</div>
				<div class="game-info">
					<h3>Game 3</h3>
					<div class="game-genre">
						<span class="magenta game-tag">RPG</span>
					</div>
					<div class="game-platform">
						<?= Icon::get('windows', 20) ?>
						<?= Icon::get('apple', 20) ?>
					</div>
				</div>
				<div class="game-price">
					<span class="current">FREE</span>
				</div>
				<div class="actions">
					<button class="remove-btn">Remove</button>
					<button class="wishlist-btn">Move to wishlist</button>
				</div>
			</div>
		</div>

		<div class="order">
			<h2>Order Summary</h2>
			<div class="order-row">
				<span>Price</span>
				<span>RM204.00</span>
			</div>
			<div class="order-row">
				<span>Sale Discount</span>
				<span class="order-discount">- RM26.00</span>
			</div>
			<div class="order-line"></div>
			<div class="order-row order-total">
				<span>Subtotal</span>
				<span>RM178.00</span>
			</div>
			<button class="checkout-btn">Checkout</button>
		</div>
    </div>
</div>