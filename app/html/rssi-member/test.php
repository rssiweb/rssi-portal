<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<script type="text/javascript" src="http://code.jquery.com/jquery.js">    </script>
<script type="text/javascript">
    $(document).ready(function(){
        $("select.country").change(function(){
            var selectedCountry = $(".country option:selected").val();
            $.ajax({
                type: "POST",
                url: "process-request.php",
                data: { country : selectedCountry } 
            }).done(function(data){
                $("#response").html(data);
            });
        });
    });
</script>

</head>
<body>
    <form>
        <table>
            <tr>
                <td>
                    <label>Country:</label>
                        <select class="country">
                            <option>Select</option>
                            <option value="usa">United States</option>
                            <option value="india">India</option>
                            <option value="uk">United Kingdom</option>
                        </select>
                </td>
                <td id="response"></td>
            </tr>
        </table>
     </form>
</body> 
</html>