<?php

namespace App\Services;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\Payer;
use PayPal\Api\PayerInfo;
use PayPal\Api\PaymentExecution;
use Request;

class PayPalService
{
	// Chứa context của API
	private $apiContext;
    // Chứa danh sách các item (mặt hàng)
    private $itemList;
    // Đơn vị tiền thanh toán
    private $paymentCurrency;
    // Tổng tiền của đơn hàng
    private $totalAmount;
    // Đường dẫn để xử lý một thanh toán thành công
    private $returnUrl;
    // Đường dẫn để xử lý khi người dùng bấm cancel (không thanh toán)
    private $cancelUrl;

    public function __construct()
    {
    	// Đọc các cài đặt trong file config
        $paypalConfigs = config('paypal');

        // Khởi tạo ngữ cảnh
        $this->apiContext = new ApiContext(
        	new OAuthTokenCredential(
            	$paypalConfigs['client_id'],
                $paypalConfigs['secret']
            )
        );
        // Set mặc định đơn vị tiền để thanh toán
        $this->paymentCurrency = "USD";

        // Khởi tạo total amount
        $this->totalAmount = 0;
    }

    /**
     * Set payment currency
     *
     * @param string $currency String name of currency
     * @return self
     */
	public function setCurrency($currency)
    {
    	$this->paymentCurrency = $currency;

        return $this;
    }

    /**
     * Get current payment currency
     *
     * @return string Current payment currency
     */
    public function getCurrency()
    {
        return $this->paymentCurrency;
    }

    /**
     * Get total amount
     *
     * @return mixed Total amount
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Set return URL
     *
     * @param string $url Return URL for payment process complete
     * @return self
     */
    public function setReturnUrl($url)
    {
        $this->returnUrl = $url;

        return $this;
    }

    /**
     * Get return URL
     *
     * @return string Return URL
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * Set cancel URL
     *
     * @param $url Cancel URL for payment
     * @return self
     */
    public function setCancelUrl($url)
    {
        $this->cancelUrl = $url;

        return $this;
    }

    /**
     * Get cancel URL of payment
     *
     * @return string Cancel URL
     */
    public function getCancelUrl()
    {
        return $this->cancelUrl;
    }

    /**
     * Create payment
     *
     * @param string $transactionDescription Description for transaction
     * @return mixed Paypal checkout URL or false
     */
    public function createPayment($transactionDescription, $payerEmail)
    {
        $checkoutUrl = false;

        // Chọn kiểu thanh toán.
        $payer = new Payer();
        $payerInfo = new PayerInfo();
        $payerInfo->setEmail($payerEmail);
        $payer->setPayerInfo($payerInfo);
        $payer->setPaymentMethod('paypal');

        // Tổng tiền và kiểu tiền sẽ sử dụng để thanh toán.
        // Bạn nên đồng nhất kiểu tiền của item và kiểu tiền của đơn hàng
        // tránh trường hợp đơn vị tiền của item là JPY nhưng của đơn hàng
        // lại là USD nhé.
        $amount = new Amount();
        $amount->setCurrency($this->paymentCurrency)
               ->setTotal($this->totalAmount);

        // Transaction
        $transaction = new Transaction();
        $transaction->setAmount($amount)
                    ->setDescription($transactionDescription);

        // Đường dẫn để xử lý một thanh toán thành công.
        $redirectUrls = new RedirectUrls();

        // Kiểm tra xem có tồn tại đường dẫn khi người dùng hủy thanh toán
        // hay không. Nếu không, mặc định chúng ta sẽ dùng luôn $redirectUrl
        if (is_null($this->cancelUrl)) {
            $this->cancelUrl = $this->returnUrl;
        }

        $redirectUrls->setReturnUrl($this->returnUrl)
                     ->setCancelUrl($this->cancelUrl);

        // Khởi tạo một payment
        $payment = new Payment();
        $payment->setIntent('Sale')
                ->setPayer($payer)
                ->setRedirectUrls($redirectUrls)
                ->setTransactions([$transaction]);

		// Thực hiện việc tạo payment
        try {
            $payment->create($this->apiContext);
        } catch (\PayPal\Exception\PPConnectionException $paypalException) {
            throw new \Exception($paypalException->getMessage());
        }

        // Nếu việc thanh tạo một payment thành công. Chúng ta sẽ nhận
        // được một danh sách các đường dẫn liên quan đến việc
        // thanh toán trên PayPal
        foreach ($payment->getLinks() as $link) {
        	// Duyệt từng link và lấy link nào có rel
            // là approval_url rồi gán nó vào $checkoutUrl
            // để chuyển hướng người dùng đến đó.
            if ($link->getRel() == 'approval_url') {
                $checkoutUrl = $link->getHref();
                break;
            }
        }

		// Trả về url thanh toán để thực hiện chuyển hướng và $payment
        return $checkoutUrl, $payment
    }
}
