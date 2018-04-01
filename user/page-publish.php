<html>
<head>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
</head>
<body>
<script type="text/javascript">
    function processImage() {
        // **********************************************
        // *** Update or verify the following values. ***
        // **********************************************

        // Replace the subscriptionKey string value with your valid subscription key.
        var subscriptionKey = "34161d2a093543aea63d8caa336e0d28";

        // Replace or verify the region.
        //
        // You must use the same region in your REST API call as you used to obtain your subscription keys.
        // For example, if you obtained your subscription keys from the westus region, replace
        // "westcentralus" in the URI below with "westus".
        //
        // NOTE: Free trial subscription keys are generated in the westcentralus region, so if you are using
        // a free trial subscription key, you should not need to change this region.
        var uriBase = "https://westcentralus.api.cognitive.microsoft.com/vision/v1.0/ocr";

        // Request parameters.
        var params = {
            "language": "unk",
            "detectOrientation ": "true",
        };

        // Display the image.
        var sourceImageUrl = document.getElementById("inputImage").value;
        document.querySelector("#sourceImage").src = sourceImageUrl;

        // Perform the REST API call.
        $.ajax({
            url: uriBase + "?" + $.param(params),

            // Request headers.
            beforeSend: function(jqXHR){
                jqXHR.setRequestHeader("Content-Type","application/json");
                jqXHR.setRequestHeader("Ocp-Apim-Subscription-Key", subscriptionKey);
            },

            type: "POST",

            // Request body.
            data: '{"url": ' + '"' + sourceImageUrl + '"}',
        })

            .done(function(data) {
                // Show formatted JSON on webpage.
                
                var val_data = data.regions[4].lines[4].words[0].text;
                   $("#responseTextArea").val(JSON.stringify(data, null, 2));
                   myOutput = document.getElementById('text');
                   myOutput.innerHTML = val_data;


            })

            .fail(function(jqXHR, textStatus, errorThrown) {
                // Display error message.
                var errorString = (errorThrown === "") ? "Error. " : errorThrown + " (" + jqXHR.status + "): ";
                errorString += (jqXHR.responseText === "") ? "" : (jQuery.parseJSON(jqXHR.responseText).message) ?
                    jQuery.parseJSON(jqXHR.responseText).message : jQuery.parseJSON(jqXHR.responseText).error.message;
                alert(errorString);
            });
    };
</script>
<?php
$max_upload_size=multichain_max_data_size()-512; // take off space for file name and mime type

if (@$_POST['publish']) {

    $upload=@$_FILES['upload'];
    $upload_file=@$upload['tmp_name'];

    if (strlen($upload_file)) {
        $upload_size=filesize($upload_file);

        if ($upload_size>$max_upload_size) {
            echo '<div class="bg-danger" style="padding:1em;">Uploaded file is too large ('.number_format($upload_size).' > '.number_format($max_upload_size).' bytes).</div>';
            return;

        } else
            $data=file_to_txout_bin($upload['name'], $upload['type'], file_get_contents($upload_file));

    } else
        $data=string_to_txout_bin($_POST['text']);

    if (no_displayed_error_result($publishtxid, multichain(
        'publishfrom', $_POST['from'], $_POST['name'], $_POST['key'], bin2hex($data)
    )))
        output_success_text('Item successfully published in transaction '.$publishtxid);
}

$labels=multichain_labels();

no_displayed_error_result($liststreams, multichain('liststreams', '*', true));

if (no_displayed_error_result($getaddresses, multichain('getaddresses', true))) {
    foreach ($getaddresses as $index => $address)
        if (!$address['ismine'])
            unset($getaddresses[$index]);

    if (no_displayed_error_result($listpermissions,
        multichain('listpermissions', 'send', implode(',', array_get_column($getaddresses, 'address')))
    ))
        $sendaddresses=array_get_column($listpermissions, 'address');
}

?>
<div class="row">

    <div class="col-sm-12">
        <h3>Publish to Stream</h3>

        <form class="form-horizontal" method="post" enctype="multipart/form-data"  action="./?chain=<?php echo html($_GET['chain'])?>&page=<?php echo html($_GET['page'])?>">
            <div class="form-group">
                <label for="from" class="col-sm-2 control-label">From address:</label>
                <div class="col-sm-9">
                    <select class="form-control" name="from" id="from">
                        <?php
                        foreach ($sendaddresses as $address) {
                            ?>
                            <option value="<?php echo html($address)?>"><?php echo format_address_html($address, true, $labels)?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="name" class="col-sm-2 control-label">Aadhaar Number:</label>
                <div class="col-sm-9">
                    <select class="form-control" name="name" id="name">
                        <?php
                        foreach ($liststreams as $stream)
                            if ($stream['name']!='root') {
                                ?>
                                <option value="<?php echo html($stream['name'])?>"><?php echo html($stream['name'])?></option>
                                <?php
                            }
                        ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="key" class="col-sm-2 control-label">Optional key:</label>
                <div class="col-sm-9">
                    <input class="form-control" name="key" id="key">
                </div>
            </div>
            <div class="form-group">
                <label for="upload" class="col-sm-2 control-label">Upload file:<br/><span style="font-size:75%; font-weight:normal;">Max <?php echo floor($max_upload_size/1024)?> KB</span></label>
                <div class="col-sm-9">
                    <input type="text" name="inputImage" id="inputImage"/>
                    <input type="button" onclick="processImage()" value="Upload"/>
                </div>
            </div>

            <div id="wrapper" style="width:1020px; display:table;">
                <div id="jsonOutput" style="width:600px; display:table-cell;">
                    Response:
                    <br><br>
                    <textarea id="responseTextArea" class="UIInput" style="width:580px; height:400px;"></textarea>
                </div>
                <div id="imageDiv" style="width:420px; display:table-cell;">
                    Source image:
                    <br><br>
                    <img id="sourceImage" width="400" />
                </div>
            </div>
            <div class="form-group">
                <div id="jsonOutput">
                    <label for="text" id='val' class="col-sm-2 control-label">Value:</label>
                    <div class="col-sm-9">
                        <textarea class="form-control" rows="4" name="text"  id="text" class="UIInput"></textarea>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-9">
                    <input class="btn btn-default" type="submit" name="publish" value="Publish Item">
                </div>
            </div>
        </form>

    </div>
</div>
</body>
</html>


