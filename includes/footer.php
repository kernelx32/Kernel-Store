<footer class="bg-gray-800 border-t border-gray-700 mt-auto">
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <a href="index.php" class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center mr-3">
                        <span class="text-white font-bold text-xl">K</span>
                    </div>
                    <span class="text-white font-bold text-xl">KernelStore</span>
                </a>
                <p class="text-gray-400 mb-6">
                    The premier marketplace for premium gaming accounts. Secure, reliable, and trusted by gamers worldwide.
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fab fa-discord"></i>
                    </a>
                </div>
            </div>
            
            <div>
                <h3 class="text-white font-semibold text-lg mb-6">Games</h3>
                <ul class="space-y-4">
                    <li><a href="game.php?id=1" class="text-gray-400 hover:text-white transition-colors">Marvel Rivals</a></li>
                    <li><a href="game.php?id=2" class="text-gray-400 hover:text-white transition-colors">Call of Duty</a></li>
                    <li><a href="game.php?id=3" class="text-gray-400 hover:text-white transition-colors">Fragpunk</a></li>
                    <li><a href="game.php?id=4" class="text-gray-400 hover:text-white transition-colors">Overwatch</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-white font-semibold text-lg mb-6">Quick Links</h3>
                <ul class="space-y-4">
                    <li><a href="accounts.php" class="text-gray-400 hover:text-white transition-colors">Browse Accounts</a></li>
                    <li><a href="boosting.php" class="text-gray-400 hover:text-white transition-colors">Boosting Services</a></li>
                    <li><a href="sell.php" class="text-gray-400 hover:text-white transition-colors">Sell Your Account</a></li>
                    <li><a href="faq.php" class="text-gray-400 hover:text-white transition-colors">FAQ</a></li>
                </ul>
            </div>
            
            <div>
                <h3 class="text-white font-semibold text-lg mb-6">Support</h3>
                <ul class="space-y-4">
                    <li><a href="contact.php" class="text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
                    <li><a href="terms.php" class="text-gray-400 hover:text-white transition-colors">Terms of Service</a></li>
                    <li><a href="privacy.php" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="refund.php" class="text-gray-400 hover:text-white transition-colors">Refund Policy</a></li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-700 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
            <p class="text-gray-400 mb-4 md:mb-0">
                &copy; <?php echo date('Y'); ?> KernelStore. All rights reserved.
            </p>
            
            <div class="flex items-center space-x-6">
                <img src="assets/images/payment/visa.png" alt="Visa" class="h-8">
                <img src="assets/images/payment/mastercard.png" alt="Mastercard" class="h-8">
                <img src="assets/images/payment/paypal.png" alt="PayPal" class="h-8">
                <img src="assets/images/payment/bitcoin.png" alt="Bitcoin" class="h-8">
            </div>
        </div>
    </div>
</footer>