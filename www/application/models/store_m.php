<?php

class Store_m extends CI_Model
{
    /**
     * [$tbl table name storage]
     * @var array
     */
    private $tbl = array(
        'store' => 'store_entries',
        'customers' => 'customers',
        'purchases' => 'purchased_items',
        'promo_emails' => 'promotional_emails',
        'admins' => 'administrators',
        'ebay' => 'auction_items'
    );

    /**
     * [__construct constructor]
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * [getCustomerCount returns the total count of customers is the customers table]
     * @return [type] []
     */
    public function getCustomerCount()
    {
        return $this->db->count_all($this->tbl['customers']);
    }

    /**
     * [getPurchasedCount the total count of purchased items]
     * @return [int] [the count]
     */
    public function getPurchasedCount()
    {
        return $this->db->count_all($this->tbl['purchases']);
    }

    /**
     * [getLastPurchased returns the last item purchased]
     * @return [obj] [the db row of the last purchased item]
     */
    public function getLastPurchased()
    {
        $query = "SELECT * FROM {$this->tbl['purchases']} ORDER BY created_at DESC LIMIT 1";
        $result = $this->db->query($query);
        $obj = $result->result_object();
        return $obj[0];
    }

    /**
     * [getStoreEntries returns all store entry data or data for the input item id]
     * @param  [int/string/array] $id [single item id to return, or an array of item ids to return, else return all]
     * @return [array]     [array of result objects, every store entry]
     */
    public function getStoreEntries($id = null)
    {
        $query = "SELECT * FROM {$this->tbl['store']} ";

        # specified an ID?
        if (!empty($id)) {
            if (is_array($id)) {
                if (!count($id)) {
                    return false;
                } else {
                    # sanitize
                    foreach ($id as $index => $data) {
                        $id[$index] = $this->db->escape($data);
                    }

                    # implode into WHERE
                    $query .= " WHERE id IN (" . implode(", ", $id) . ") ";
                }
            } else {
                # sanitize our inputs which also adds quotes
                $id = $this->db->escape($id);
                $query .= " WHERE id = {$id} ";
            }
        }

        # order by
        $query .= " ORDER BY id DESC";

        # grab data
        $result = $this->db->query($query);
        return $result->result_object();
    }

    /**
     * [getItemNameById lookup the name of an item by its store id]
     * @param  [type] $id [the item_id we're looking up]
     * @return [string]     [the item name]
     */
    public function getItemNameById($id)
    {
        if (empty($id)) {
            return false;
        } else {
            # sanitize our inputs which also adds quotes
            $id = $this->db->escape($id);
        }

        $query = "SELECT name FROM {$this->tbl['store']} WHERE id = {$id} LIMIT 1";
        $result = $this->db->query($query);
        $obj = $result->result_object();
        return $obj[0]->name;
    }

    /**
     * [createUser creates a user with the input email and generates a random password for that user_error()
     *     if the input email is already in use, the found user is returned]
     * @param  [string] $email [the email address for this user, required]
     * @return [result object]        [the db row of the created user]
     */
    public function createUser($email)
    {
        if (empty($email)) {
            return false;
        } else {
            # sanitize our inputs which also adds quotes
            $email = $this->db->escape($email);
        }

        # first make sure the user isn't already in the system
        $query = "SELECT * FROM {$this->tbl['customers']} WHERE email = {$email} LIMIT 1";
        $result = $this->db->query($query);
        if ($result->num_rows() > 0) {
            return $result->row();
        } else {
            # create the user
            $query = "INSERT INTO {$this->tbl['customers']} SET email = {$email}, password = '" . generateRandPassword(6) . "'";
            $result = $this->db->query($query);

            # add this email to the list of promo emails
            $this->subscribeToPromos($email);

            # return the created user
            $query = "SELECT * FROM {$this->tbl['customers']} WHERE email = {$email} LIMIT 1";
            $result = $this->db->query($query);
            return $result->row();
        }
        return false;
    }

    /**
     * [getUserByEmail gets the DB row FROM the customers table for the input email]
     * @param  [string] $email [the email we are looking up.  required, return false if not input]
     * @return [object/bool]        [one db row FROM the customers table for this user, false if not found]
     */
    public function getUserByEmail($email)
    {
        if (empty($email)) {
            return false;
        } else {
            # sanitize our inputs which also adds quotes
            $email = $this->db->escape($email);
        }

        $query = "SELECT * FROM {$this->tbl['customers']} WHERE email = {$email}";

        # select the data and return
        $result = $this->db->query($query);
        if ($result->num_rows() > 0) {
            return $result->row();
        } else {
            return false;
        }
    }

    /**
     * [verifyLogin used to verify if the input login and password are correct]
     * @param  [string] $email    [the user's email]
     * @param  [string] $password [the user's password]
     * @return [obj/bool]         [a user row object if the data is correct, else false]
     */
    public function verifyLogin($email, $password)
    {
        if (empty($email) || empty($password)) {
            return false;
        } else {
            # sanitize our inputs which also adds quotes
            $email = $this->db->escape($email);
            $password = $this->db->escape($password);
        }

        $query = "SELECT * FROM {$this->tbl['customers']} WHERE email = {$email} AND password = {$password}";

        # select the data and return
        $result = $this->db->query($query);
        if ($result->num_rows() > 0) {
            return $result->row();
        } else {
            return false;
        }
    }

    /**
     * [verifyAdminLogin description]
     * @param  [type] $login    [description]
     * @param  [type] $password [description]
     * @return [type]           [description]
     */
    public function verifyAdminLogin($login, $password)
    {
        if (empty($login) || empty($password)) {
            return false;
        } else {
            # santiize our inputs
            $login = $this->db->escape($login);
            $password = $this->db->escape($password);
        }

        $query = "SELECT * FROM {$this->tbl['admins']} WHERE login = {$login} and password = md5({$password})";

        # select the data and return
        $result = $this->db->query($query);
        if ($result->num_rows() > 0) {
            return $result->row();
        } else {
            return false;
        }
    }

    /**
     * [getUserById gets the DB row FROM the customers table for the input user_id]
     * @param  [type] $user_id [the user_id we are looking up.  required, false if not input]
     * @return [object/bool] [one db row FROM the customers table for this user, false if not found]
     */
    public function getUserById($user_id)
    {
        if (empty($user_id)) {
            return false;
        } else {
            # sanitize our inputs which also adds quotes
            $user_id = $this->db->escape($user_id);
        }

        $query = "SELECT * FROM {$this->tbl['customers']} WHERE id = {$user_id}";
        $result = $this->db->query($query);

        if ($result->num_rows() > 0) {
            return $result->row();
        } else {
            return false;
        }
    }

    /**
     * [purchaseItem adds a record for the input user_id and item_id to the purchased_items table to be 'purchased']
     * @param  [int/string] $user_id  [the user_id this item is being tied to, required]
     * @param  [int/string] $item_id  [the item_id that the user is purchasing, required]
     * @param  [float]   $amount_paid   [if the user paid money, how much for this item?]
     * @return [void]
     */
    public function purchaseItem($user_id, $item_id, $amount_paid = 0.00)
    {
        if (empty($user_id) || empty($item_id)) {
            return false;
        } else {
            # was it free? - do before escape() which adds quotes
            if ($amount_paid === "0.00") {
                $free_purchase = 'true';
            } else {
                $free_purchase = 'false';
            }

            # sanitize our inputs which also adds quotes
            $user_id = $this->db->escape($user_id);
            $item_id = $this->db->escape($item_id);
            $amount_paid = $this->db->escape($amount_paid);
        }

        # first see if this item has already been purchased
        $query = "SELECT * FROM {$this->tbl['purchases']}
            WHERE customer_id = {$user_id} AND store_entry_id = {$item_id}";
        $result = $this->db->query($query);
        $already_purchased = $result->num_rows();

        # if it's already purhcased, update.  else insert
        if ($already_purchased) {
            # just update the date
            $query = "UPDATE {$this->tbl['purchases']} SET created_at = now(),
                free_purchase = {$free_purchase}, amount_paid = {$amount_paid}
                WHERE customer_id = {$user_id} AND store_entry_id = {$item_id} LIMIT 1";
            $this->db->query($query);
        } else {
            # not already purchased, purchase it
            $query = "INSERT INTO {$this->tbl['purchases']}
                SET created_at = now(), customer_id = {$user_id}, store_entry_id = {$item_id},
                free_purchase = {$free_purchase}, amount_paid = {$amount_paid}";
            $this->db->query($query);
        }
    }

    /**
     * [getPurchasesByUserId takes the input user_id and gets all of that users purchased items]
     * @param  [type] $user_id [the user_id we are looking for items]
     * @return [array]          [an array of item objects.  the array will be empty if nothing is found]
     */
    public function getPurchasesByUserId($user_id)
    {
        if (empty($user_id)) {
            return false;
        } else {
            # sanitize our inputs which also adds quotes
            $user_id = $this->db->escape($user_id);
        }

        # get all the items this user has purchased
        $query = "SELECT * FROM {$this->tbl['purchases']}
            JOIN {$this->tbl['store']} ON {$this->tbl['purchases']}.store_entry_id = {$this->tbl['store']}.id
            WHERE customer_id = {$user_id}";
        $result = $this->db->query($query);
        return $result->result_object();
    }

    /**
     * [removeStrandedPurchases removes records in the purchases table that are tied to un-known customer IDs]
     * @return [type] [description]
     */
    public function removeStrandedPurchases()
    {
        # how many records will we be deleting?
        $query = "SELECT * FROM {$this->tbl['purchases']} WHERE {$this->tbl['purchases']}.customer_id NOT IN (SELECT id from {$this->tbl['customers']}) LIMIT 5";
        $result = $this->db->query($query);
        $result = $result->num_rows();

        # delete the records
        $query = "DELETE FROM {$this->tbl['purchases']} WHERE {$this->tbl['purchases']}.customer_id NOT IN (SELECT id from {$this->tbl['customers']}) LIMIT 5";
        $this->db->query($query);

        return $result;
    }

    /**
     * [removeTestAccounts deletes all customers with an email like '%marcero%']
     * @return [type] [description]
     */
    public function removeTestAccounts()
    {
        # how many records will we be deleting?
        $query = "SELECT * FROM {$this->tbl['customers']} WHERE {$this->tbl['customers']}.email LIKE '%marcero%' LIMIT 5";
        $result = $this->db->query($query)->result_object();

        # delete the records
        $query = "DELETE FROM {$this->tbl['customers']} WHERE {$this->tbl['customers']}.email LIKE '%marcero%' LIMIT 5";
        $this->db->query($query);

        return $result;
    }

    /**
     * [removeTestPromos deletes all promo email records with an email like '%marcero%']
     * @return [type] [description]
     */
    public function removeTestPromos()
    {
        # how many records will we be deleting?
        $query = "SELECT * FROM {$this->tbl['promo_emails']} WHERE {$this->tbl['promo_emails']}.email LIKE '%marcero%' LIMIT 5";
        $result = $this->db->query($query)->result_object();

        # delete the records
        $query = "DELETE FROM {$this->tbl['promo_emails']} WHERE {$this->tbl['promo_emails']}.email LIKE '%marcero%' LIMIT 5";
        $this->db->query($query);

        return $result;
    }

    /**
     * [getPromoEmails returns a single dimension array of all promo emails]
     * @return [array] [array of emails]
     */
    public function getPromoEmails()
    {
        # get the emails
        $query = "SELECT email FROM {$this->tbl['promo_emails']}";
        $result = $this->db->query($query)->result_array();

        # make into a single dimension
        $emails = array();
        foreach ($result as $row) {
            $emails[] = $row['email'];
        }

        return $emails;
    }

    /**
     * [removePromoEmail removes the input promo email from the promo email table]
     * @param  [type] $email [the email we want to remove]
     * @return [bool]        [true if the email was found and removed, false if the email was not found or not input]
     */
    public function removePromoEmail($email)
    {
        if (empty($email)) {
            return false;
        } else {
            # santiize our input
            $email = $this->db->escape($email);
        }

        # does the email exist?
        $find_query = "SELECT * FROM {$this->tbl['promo_emails']} WHERE email = {$email}";
        $find_result = $this->db->query($find_query)->result_array();
        if (!count($find_result)) {
            return false;
        } else {
            # we found the email, remove it.  the email field is unique, we can limit to 1 for safety
            $delete_query = "DELETE FROM {$this->tbl['promo_emails']} WHERE email = {$email} LIMIT 1";
            $this->db->query($delete_query);
            return true;
        }

    }

    public function stats_SalesCountsSinceLastRelease()
    {
        $query = "SELECT count(*), {$this->tbl['store']}.name FROM {$this->tbl['purchases']}
            JOIN {$this->tbl['store']} ON {$this->tbl['purchases']}.store_entry_id = {$this->tbl['store']}.id
            WHERE {$this->tbl['purchases']}.created_at >=
                (SELECT max({$this->tbl['store']}.first_sale) FROM {$this->tbl['store']})
            GROUP BY store_entry_id ORDER BY count(*) DESC";
    }

    public function stats_PercentFreePurchases()
    {
        $query = "SELECT (sum(free_purchase) /
            (SELECT count(*) FROM $this->tbl['purchases']} WHERE free_purchase IS NOT NULL)) * 100
            FROM $this->tbl['purchases']}";
    }

    public function stats_AvgPricePaid()
    {
        $query = "SELECT avg(amount_paid)
            FROM $this->tbl['purchases']}
            WHERE amount_paid > 0";
    }

    public function stats_AvgPricePaidByItem()
    {
        $query = "SELECT round(avg(amount_paid), 2) AS avg_paid, name
            FROM {$this->tbl['purchases']}
            JOIN {$this->tbl['store']} ON {$this->tbl['store']}.id = {$this->tbl['purchases']}.store_entry_id
            WHERE amount_paid > 0 GROUP BY {$this->tbl['purchases']}.store_entry_id";
    }

    public function stats_TotalIncomeByItem()
    {
        $query = "SELECT round(sum(amount_paid), 2) AS avg_paid, name
            FROM {$this->tbl['purchases']}
            JOIN {$this->tbl['store']} ON {$this->tbl['store']}.id = {$this->tbl['purchases']}.store_entry_id
            WHERE amount_paid > 0 GROUP BY {$this->tbl['purchases']}.store_entry_id";
    }

    public function stats_TotalPurchasesByMonth()
    {
        $query = "SELECT count(*) AS purchases, left(created_at, 7) AS month
            FROM {$this->tbl['purchases']}
            GROUP BY month ORDER BY month";
    }

    public function stats_TotalIncomeByMonth()
    {
        $query = "SELECT sum(amount_paid) AS total_income, left(created_at, 7) AS month
            FROM {$this->tbl['purchases']}
            WHERE amount_paid IS NOT NULL
            GROUP BY month ORDER BY month";
    }

    /**
     * [subscribeToPromos if the email is valid and not already in the promos table, it is added and true is returned
     *     else, false is returned
     *     the email msut already be valid and escaped, which is why the method is kept private]
     * @param  [type] $email [the email we are adding to the promos table]
     * @return [bool]        [true if the email was added, false if the email was invalid or already in the promos table]
     */
    private function subscribeToPromos($email)
    {
        # is the email already in the db?
        $query = "SELECT * FROM {$this->tbl['promo_emails']} WHERE email = {$email}";
        $email_count = $this->db->query($query)->num_rows();

        # if it is not in the db, insert.  else return false
        if (!$email_count) {
            $query = "INSERT INTO {$this->tbl['promo_emails']} SET email = {$email}";
            $result = $this->db->query($query);
            return true;
        } else {
            return false;
        }
    }
}
