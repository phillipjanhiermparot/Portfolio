<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPHSL University Supply Center Kiosk</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 80vh;
            margin: 20;
        }

        .content-container {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 45%;
            padding: 20px;
        }

        .container {
            border: 2px solid #7380ec;
            padding: 40px;
            border-radius: 10px;
            max-width: 600px;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Increased font size for better readability */
        h2,
        label,
        input,
        select {
            font-size: 20px;
        }

        /* Increased button size and centered buttons */
        .button-container {
            display: flex;
            justify-content: center;
        }

        .button-container button {
            padding: 15px 30px;
            font-size: 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background-color: #7380ec;
            color: #fff;
            transition: background-color 0.3s ease;
        }

        .button-container button:hover {
            background-color: #5c67c4;
        }

        /* Adjusted button style */
        .button-container button:first-child {
            margin-right: 10px;
        }

        /* Style for back button */
        .back-button {
            padding: 15px 30px; /* Adjusted padding to match other buttons */
            font-size: 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background-color: #7380ec;
            color: #fff;
            transition: background-color 0.3s ease;
            margin-top: 20px; /* Added margin-top */
        }

        .back-button:hover {
            background-color: #5c67c4;
        }
    </style>
</head>
<body>

<div class="content-container">
    <div class="container">
        <h2>Select a Product</h2>
        <form class="product-form" action="record_order.php" onsubmit="addToCart(); return false;">
            <label for="product">Product:</label>
            <select id="product" name="product">
                <option value="----">----</option>
                <option value="C_Blouse" data-price="500">College Blouse - ₱500</option>
                <option value="C_Polo Barong" data-price="625">College Polo Barong - ₱625</option>
                <option value="C_Skirt" data-price="550">College Skirt - ₱550</option>
                <option value="C_PE Tshirt" data-price="480">College PE Tshirt - ₱480</option>
                <option value="C_PE Pants" data-price="570">College PE Pants - ₱570</option>
                <option value="Perpetual Tshirt" data-price="500">Perpetual Tshirt - ₱500</option>
                <option value="C_Jacket" data-price="950">College Jacket - ₱950</option>
                <option value="NSTP" data-price="380">NSTP Tshirt - ₱380</option>             
                <option value="CAS_P-Uniform Male" data-price="935">Psyhcology - Uniform Male (1 set) - ₱935</option>
                <option value="CAS_P-Uniform Female" data-price="880">Psychology - Uniform Female (1 set) - ₱880</option>
                <option value="CIHM_T-Uniform Male" data-price="1500">Tourism - Uniform Male (1 set) - ₱1500</option>
                <option value="CIHM_T-Uniform Female" data-price="1780">Tourism - Uniform Female (1 set) - ₱1780</option>
                <option value="CIHM_ND -Uniform Female" data-price="880">Nutrition and Dietetics - Uniform (1 set) - ₱880</option>
                <option value="CME_MT Uniform" data-price="1200">Maritime Uniform (1 set) - ₱1200</option>
                <option value="Aviation/Crim_Uniform" data-price="1450">Uniform (1 set) - ₱1450</option>
                <option value="Engineering_BP" data-price="595">Blue Pants - ₱595</option>
                <option value="Products_School ID Lace w Case" data-price="175">School ID Lace w Case - ₱175</option>
                <option value="Products_MTParaphenalia" data-price="285">Maritime Paraphenalia (each) - ₱285</option>
                <option value="Products_BnBs" data-price="300">Belt and Buckles - ₱300</option>
                <option value="Products_MNP" data-price="350">Magnetic Name Plate - ₱350</option>
                <option value="NKP_Blouse" data-price="320">NKP - Blouse - ₱320</option>
                <option value="NKP_Jumper" data-price="375">NKP - Jumper - ₱375</option>
                <option value="E_Short" data-price="275">Elementary - Short - ₱275</option>
                <option value="E_Skirt" data-price="550">Elementary - Skirt - ₱550</option>
                <option value="E-PoloJack1" data-price="300">Elementary Polo Jacket (#8-#18) - ₱300 </option>
                <option value="E-PoloJack2" data-price="300">Elementary Polo Jacket (S-3XL) - ₱430 </option>
                <option value="G1-3_Blouse" data-price="325">Grade 1 - 3 Blouse - ₱325</option>
                <option value="G4-6_Blouse" data-price="400">Grade 4 - 6 Blouse - ₱400</option>
                <option value="JHS_Blouse" data-price="500"> Junior Highschool Blouse - ₱500</option>
                <option value="JHS_Polo Barong" data-price="625"> Junior Highschool Polo Barong - ₱625</option>
                <option value="SHS_Blazer" data-price="1600"> Senior Highschool Blazer 1 Set (MALE) - ₱1600</option>
                <option value="SHS_Blazer" data-price="1800"> Senior Highschool Blazer 1 Set (FEMALE) - ₱1800</option>
                <option value="SHS_Vest" data-price="1350"> Senior Highschool Vest 1 Set (MALE) - ₱1350</option>
                <option value="SHS_Vest" data-price="1450"> Senior Highschool Vest 1 Set (FEMALE) - ₱1450</option>
                <option value="JHS_Polo Barong" data-price="625"> Junior Highschool Polo Barong - ₱625</option>


                
            </select><br><br>
            
            <label for="size">Size:</label>
            <select id="size" name="size">
                <option value="----">----</option>
                <option value="XS">XS</option>
                <option value="S">S</option>
                <option value="M">M</option>
                <option value="L">L</option>
                <option value="XL">XL</option>
                <option value="2XL">2XL</option>
                <option value="3XL">3XL</option>
                <option value="C-SW25">College Skirt - W25</option>
                <option value="C-SW26">College Skirt - W26</option>
                <option value="C-SW27">College Skirt - W27</option>
                <option value="C-SW28">College Skirt - W28</option>
                <option value="C-SW29">College Skirt - W29</option>
                <option value="C-SW30">College Skirt - W30</option>
                <option value="C-SW31">College Skirt - W31</option>
                <option value="C-SW32">College Skirt - W32</option>
                <option value="E-SHW12">Elementary Short - W12</option>
                <option value="E-SHW13">Elementary Short - W13</option>
                <option value="E-SHW14">Elementary Short - W14</option>
                <option value="E-SHW15">Elementary Short - W15</option>
                <option value="E-SHW16">Elementary Short - W16</option>
                <option value="E-SHW17">Elementary Short - W17</option>
                <option value="E-SHW18">Elementary Short - W18</option>
                <option value="E-SHW19">Elementary Short - W19</option>
                <option value="E-SHW20">Elementary Short - W20</option>
                <option value="E-SKW19">Elementary Skirt - W19</option>
                <option value="E-SKW20">Elementary Skirt - W20</option>
                <option value="E-SKW21">Elementary Skirt - W21</option>
                <option value="E-SKW22">Elementary Skirt - W22</option>
                <option value="E-SKW23">Elementary Skirt - W23</option>
                <option value="E-SKW24">Elementary Skirt - W24</option>
                <option value="E-PJ8">Elementary Polo Jacket - #8</option>
                <option value="E-PJ9">Elementary Polo Jacket - #9</option>
                <option value="E-PJ10">Elementary Polo Jacket - #10</option>
                <option value="E-PJ11">Elementary Polo Jacket - #11</option>
                <option value="E-PJ12">Elementary Polo Jacket - #12</option>
                <option value="E-PJ14">Elementary Polo Jacket - #14</option>
                <option value="E-PJ16">Elementary Polo Jacket - #16</option>
                <option value="E-PJ18">Elementary Polo Jacket - #18</option>



            </select><br><br>
            
            <label for="quantity">Quantity:</label>
            <input type="number" id="quantity" name="quantity" min="1" value="1"><br><br>
            
            <!-- Include a hidden input field to store the price -->
            <input type="hidden" id="price" name="price">
        </form>
    </div>
    <br><br>
    <div class="button-container">
        <div>
            <button type="submit" onclick="addToCart()">Add to Cart</button>
        </div>
        <div>
            <button type="button" onclick="redirectToCheckout()">View Cart</button>
        </div>
    </div>

    <!-- Back button -->
    <button class="back-button" onclick="goBack()">Back</button>
</div>

<script>
    function addToCart() {
        var product = document.getElementById("product").value;
        var size = document.getElementById("size").value;
        var quantity = document.getElementById("quantity").value;
        var department = new URLSearchParams(window.location.search).get('department');
        
        // Get the selected option and its data-price attribute
        var selectedOption = document.getElementById("product").selectedOptions[0];
        var price = selectedOption.getAttribute("data-price");
        
        // Set the price value in the hidden input field
        document.getElementById("price").value = price;

        // Send the data to your server
        var formData = new FormData();
        formData.append('product', product);
        formData.append('size', size);
        formData.append('quantity', quantity);
        formData.append('price', price); // Append price
        formData.append('department', department);

        fetch('record_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            console.log(data); // Log the response from the server
            // Show alert
            if (confirm("'" + product + "' added to cart. Do you want to add more?")) {
                // Do nothing, user wants to add more
            } else {
                // Redirect to checkout page
                window.location.href = "checkout.php?department=" + department;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function redirectToCheckout() {
        var department = new URLSearchParams(window.location.search).get('department');
        window.location.href = "checkout.php?department=" + department;
    }

    function goBack() {
        window.history.back();
    }
</script>

</body>
</html>
