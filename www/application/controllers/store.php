<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Store extends CI_Controller {

    /**
     * [__construct constructor]
     */
    public function __construct()
    {
        parent::__construct();

        # load dependencies
        $this->load->helper('store_helper');
        $this->load->model('store_m');

        # load the header for every store page
        $this->loadHeader();
    }

    /**
     * [index description]
     * @return [type] [description]
     */
	public function index()
	{
        # load the store
        $items = $this->store_m->getStoreEntries();
        $this->load->view('items', array('items' => $items));

        $this->loadFooter();
	}

    public function about()
    {
        $this->load->view('about');
        $this->loadFooter();
    }

    /**
     * [loadHeader grabs the required data and loads the header]
     * @return [type] [description]
     */
    private function loadHeader()
    {
        # data to pass to the view
        $data = array();

        # get header counts
        $data['customer_count'] = $this->store_m->getCustomerCount();
        $data['purchased_count'] = $this->store_m->getPurchasedCount();

        # get last purchased
        $item = $this->store_m->getLastPurchased();
        $data['last_purchase'] = prettyTime(strtotime($item->created_at));

        # set our data and load the header
        $this->load->view('header', $data);
    }

    /**
     * [loadFooter grabs the required data and loads the footer]
     * @return [type] [description]
     */
    private function loadFooter()
    {
        $this->load->view('footer');
    }
}
