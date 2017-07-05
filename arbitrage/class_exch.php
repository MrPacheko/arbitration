<?php
// Exchange Class
class Exchange_usdtbtc
{
    //Attributes :
    private $name_exch; //Exchange name
    private $url_get_orders_usdtbtc; //Url to get order book for usdtbtc
    private $max_quantity_btc; //Quantity available on btc wallet
    private $max_quantity_usdt; //Quantity available on usdt wallet
    private $btc_transfert_cost; //Cost to transfert btc from Exchange (in btc)
    private $usdt_transfert_cost; //Cost to transfert usdt from Exchange (in usdt)
    private $f_sell; //Btc sell fee (% of usdt)
    private $f_buy; //Btc buy fee (% of btc)
    
    //Get Methods
    public function get_name_exch(){ return $this->name_exch; }
    public function get_url_get_orders_usdtbtc(){ return $this->url_get_orders_usdtbtc; }
    public function get_max_quantity_btc(){ return $this->max_quantity_btc; }
    public function get_max_quantity_usdt(){ return $this->max_quantity_usdt; }
    public function get_btc_transfert_cost(){ return $this->btc_transfert_cost; }
    public function get_usdt_transfert_cost(){ return $this->usdt_transfert_cost; }
    public function get_f_sell(){ return $this->f_sell; }
    public function get_f_buy(){ return $this->f_buy; }
    
    //Set methods
    public function set_name_exch($new_name_exch){ $this->name_exch=$new_name_exch;}
    public function set_url_get_orders_usdtbtc($new_url_get_orders_usdtbtc){ $this->url_get_orders_usdtbtc=$new_url_get_orders_usdtbtc;}
    public function set_max_quantity_btc($new_max_quantity_btc){ $this->max_quantity_btc=$new_max_quantity_btc;}
    public function set_max_quantity_usdt($new_max_quantity_usdt){ $this->max_quantity_usdt=$new_max_quantity_usdt;}
    public function set_btc_transfert_cost($new_btc_transfert_cost){ $this->btc_transfert_cost=$new_btc_transfert_cost;}
    public function set_usdt_transfert_cost($new_usdt_transfert_cost){ $this->usdt_transfert_cost=$new_usdt_transfert_cost;}
    public function set_f_sell($new_f_sell){ $this->f_sell=$new_f_sell;}
    public function set_f_buy($new_f_buy){ $this->f_buy=$new_f_buy;}
    
    public function update_max_quantity_wallet(){
        $this->max_quantity_btc = 1;
        $this->max_quantity_usdt = 3000;
    }
    
    //Function that returns json order book for usdtBtc
    public function get_data_usdtbtc(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$this->get_url_get_orders_usdtbtc());
        $result = curl_exec($ch);
        curl_close($ch);
        $obj = json_decode($result);
        return $obj;
    }
    
    //Hydrate exchange
    function hydrate_exchange(
        $name_exch,
        $url_get_orders_usdtbtc,
        $btc_transfert_cost,
        $usdt_transfert_cost,
        $f_sell,
        $f_buy
    ){
        $this->set_name_exch($name_exch);
        $this->set_url_get_orders_usdtbtc($url_get_orders_usdtbtc);
        $this->set_btc_transfert_cost($btc_transfert_cost);
        $this->set_usdt_transfert_cost($usdt_transfert_cost);
        $this->set_f_sell($f_sell);
        $this->set_f_buy($f_buy);
    }
    
    //Function that wrap the exchange order book usdtbtc
    //Return a json with 5 first sell and buy orders with prices and quantities
    //Chaping depends on the exchange
    public function shape_order_book_usdt_btc(){
        $this->update_max_quantity_wallet();
        $json = $this->get_data_usdtbtc();
        $name_exchange = $this->get_name_exch();
        $result = 0;
        
        if($name_exchange == 'bitfinex'){
            $result = array(
                "exch"=>$name_exchange,
                "btc_wallet_quantity"=>$this->get_max_quantity_btc(),
                "usdt_wallet_quantity"=>$this->get_max_quantity_usdt(),
                "buys"=>[
                    array("price"=> $json->{"bids"}[0]->{"price"}, "quantity"=> $json->{"bids"}[0]->{"amount"} ),
                    array("price"=> $json->{"bids"}[1]->{"price"}, "quantity"=> $json->{"bids"}[1]->{"amount"} ),
                    array("price"=> $json->{"bids"}[2]->{"price"}, "quantity"=> $json->{"bids"}[2]->{"amount"} ),
                    array("price"=> $json->{"bids"}[3]->{"price"}, "quantity"=> $json->{"bids"}[3]->{"amount"} ),
                    array("price"=> $json->{"bids"}[4]->{"price"}, "quantity"=> $json->{"asks"}[4]->{"amount"})
                ],
                "sells"=>[
                    array("price"=> $json->{"asks"}[0]->{"price"}, "quantity"=> $json->{"asks"}[0]->{"amount"} ),
                    array("price"=> $json->{"asks"}[1]->{"price"}, "quantity"=> $json->{"asks"}[1]->{"amount"} ),
                    array("price"=> $json->{"asks"}[2]->{"price"}, "quantity"=> $json->{"asks"}[2]->{"amount"} ),
                    array("price"=> $json->{"asks"}[3]->{"price"}, "quantity"=> $json->{"asks"}[3]->{"amount"} ),
                    array("price"=> $json->{"asks"}[4]->{"price"}, "quantity"=> $json->{"asks"}[4]->{"amount"} )
                ]
            );
        }
        elseif($name_exchange == 'poloniex'){
            $result = array(
                "exch"=>$name_exchange,
                "btc_wallet_quantity"=>$this->get_max_quantity_btc(),
                "usdt_wallet_quantity"=>$this->get_max_quantity_usdt(),
                "buys"=>[
                    array("price"=> $json->{"bids"}[0][0], "quantity"=> $json->{"bids"}[0][1] ),
                    array("price"=> $json->{"bids"}[1][0], "quantity"=> $json->{"bids"}[1][1] ),
                    array("price"=> $json->{"bids"}[2][0], "quantity"=> $json->{"bids"}[2][1] ),
                    array("price"=> $json->{"bids"}[3][0], "quantity"=> $json->{"bids"}[3][1] ),
                    array("price"=> $json->{"bids"}[4][0], "quantity"=> $json->{"bids"}[3][1] )
                ],
                "sells"=>[
                    array("price"=> $json->{"asks"}[0][0], "quantity"=> $json->{"asks"}[0][1] ),
                    array("price"=> $json->{"asks"}[1][0], "quantity"=> $json->{"asks"}[1][1] ),
                    array("price"=> $json->{"asks"}[2][0], "quantity"=> $json->{"asks"}[2][1] ),
                    array("price"=> $json->{"asks"}[3][0], "quantity"=> $json->{"asks"}[3][1] ),
                    array("price"=> $json->{"asks"}[4][0], "quantity"=> $json->{"asks"}[3][1] )
                ]
            );
        }
        elseif($name_exchange == 'bittrex'){
            $result = array(
                "exch"=>$name_exchange,
                "btc_wallet_quantity"=>$this->get_max_quantity_btc(),
                "usdt_wallet_quantity"=>$this->get_max_quantity_usdt(),
                "buys"=>[
                    array("price"=> $json->{"result"}->{"buy"}[0]->{'Rate'}, "quantity"=> $json->{"result"}->{"buy"}[0]->{'Quantity'}),
                    array("price"=> $json->{"result"}->{"buy"}[1]->{'Rate'}, "quantity"=> $json->{"result"}->{"buy"}[1]->{'Quantity'}),
                    array("price"=> $json->{"result"}->{"buy"}[2]->{'Rate'}, "quantity"=> $json->{"result"}->{"buy"}[2]->{'Quantity'}),
                    array("price"=> $json->{"result"}->{"buy"}[3]->{'Rate'}, "quantity"=> $json->{"result"}->{"buy"}[3]->{'Quantity'}),
                    array("price"=> $json->{"result"}->{"buy"}[4]->{'Rate'}, "quantity"=> $json->{"result"}->{"buy"}[4]->{'Quantity'})
                ],
                "sells"=>[
                    array("price"=> $json->{"result"}->{"sell"}[0]->{'Rate'}, "quantity"=> $json->{"result"}->{"sell"}[0]->{'Quantity'}),
                    array("price"=> $json->{"result"}->{"sell"}[1]->{'Rate'}, "quantity"=> $json->{"result"}->{"sell"}[1]->{'Quantity'}),
                    array("price"=> $json->{"result"}->{"sell"}[2]->{'Rate'}, "quantity"=> $json->{"result"}->{"sell"}[2]->{'Quantity'}),
                    array("price"=> $json->{"result"}->{"sell"}[3]->{'Rate'}, "quantity"=> $json->{"result"}->{"sell"}[3]->{'Quantity'}),
                    array("price"=> $json->{"result"}->{"sell"}[4]->{'Rate'}, "quantity"=> $json->{"result"}->{"sell"}[4]->{'Quantity'})
                ]
            );
        }
        
        $result = json_encode($result);
        $result = json_decode($result);
        
        return $result;
    }
}