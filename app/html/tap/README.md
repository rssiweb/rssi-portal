 <?php
                                                    // Extract file ID from Google Drive link
                                                    function extract_file_id($url)
                                                    {
                                                        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)\//', $url, $matches)) {
                                                            return $matches[1];
                                                        }
                                                        return null;
                                                    }

                                                    // Function to get file name from Google Drive using file ID
                                                    function get_file_name_from_google_drive($file_id, $api_key)
                                                    {
                                                        $url = "https://www.googleapis.com/drive/v3/files/$file_id?fields=name&key=$api_key";

                                                        $ch = curl_init();
                                                        curl_setopt($ch, CURLOPT_URL, $url);
                                                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                                                        $response = curl_exec($ch);
                                                        curl_close($ch);

                                                        $data = json_decode($response, true);
                                                        return $data['name'] ?? 'Unknown File';
                                                    }

                                                    $api_key = "AIzaSyCtWC48inXWXUM8s6hSeX89LP78sfGLk_g"; // Replace with your actual Google Drive API Key
                                                    ?>
                                                    <?php
                                                    $resume_file_id = extract_file_id($array['resume_upload']);
                                                    $resume_filename = $resume_file_id ? get_file_name_from_google_drive($resume_file_id, $api_key) : null;
                                                    ?>
                                                    <td>
                                                    <input type="file" class="form-control" id="resume-upload" name="resume-upload">

                                                    <?php if (!empty($resume_filename)): ?>
                                                        <div>
                                                            <a href="<?php echo htmlspecialchars($array['resume_upload']); ?>" target="_blank"><?php echo htmlspecialchars($resume_filename); ?></a>
                                                        </div>
                                                    <?php endif; ?>

                                                    <small id="resume-upload-help" class="form-text text-muted">
                                                        Please upload a scanned copy of the Resume.
                                                    </small>
                                                </td>