<?php
/**
 * Documentation Template for Gaenity Community Plugin
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gaenity Community - Documentation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background: #f8fafc;
        }
        .docs-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .docs-header {
            background: linear-gradient(135deg, #1d4ed8, #7c3aed);
            color: white;
            padding: 3rem 2rem;
            border-radius: 16px;
            margin-bottom: 3rem;
            text-align: center;
        }
        .docs-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .docs-nav {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            position: sticky;
            top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .docs-nav h3 {
            margin-bottom: 1rem;
            color: #1d4ed8;
        }
        .docs-nav ul {
            list-style: none;
        }
        .docs-nav li {
            margin-bottom: 0.5rem;
        }
        .docs-nav a {
            color: #475569;
            text-decoration: none;
            padding: 0.5rem;
            display: block;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .docs-nav a:hover {
            background: #f1f5f9;
            color: #1d4ed8;
            transform: translateX(4px);
        }
        .docs-content {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
        }
        .docs-main {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .docs-section {
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #f1f5f9;
        }
        .docs-section:last-child {
            border-bottom: none;
        }
        .docs-section h2 {
            color: #1d4ed8;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }
        .docs-section h3 {
            color: #334155;
            margin: 1.5rem 0 1rem;
            font-size: 1.3rem;
        }
        .shortcode-box {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-family: 'Monaco', 'Courier New', monospace;
            margin: 1rem 0;
            position: relative;
            overflow-x: auto;
        }
        .shortcode-box code {
            color: #93c5fd;
        }
        .copy-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: #475569;
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .copy-btn:hover {
            background: #1d4ed8;
        }
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #1d4ed8;
            padding: 1rem 1.5rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem 1.5rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 1rem 1.5rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f8fafc;
            font-weight: 600;
            color: #1e293b;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .feature-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        .feature-card h4 {
            color: #1d4ed8;
            margin-bottom: 0.5rem;
        }
        @media (max-width: 768px) {
            .docs-content {
                grid-template-columns: 1fr;
            }
            .docs-nav {
                position: static;
            }
        }
    </style>
</head>
<body>
    <div class="docs-container">
        <div class="docs-header">
            <h1>📚 Gaenity Community Plugin</h1>
            <p>Complete Documentation & Setup Guide</p>
        </div>

        <div class="docs-content">
            <aside class="docs-nav">
                <h3>Table of Contents</h3>
                <ul>
                    <li><a href="#getting-started">Getting Started</a></li>
                    <li><a href="#shortcodes">All Shortcodes</a></li>
                    <li><a href="#pages">Page Setup</a></li>
                    <li><a href="#settings">Settings</a></li>
                    <li><a href="#admin">Admin Features</a></li>
                    <li><a href="#workflows">User Workflows</a></li>
                    <li><a href="#customization">Customization</a></li>
                    <li><a href="#troubleshooting">Troubleshooting</a></li>
                </ul>
            </aside>

            <main class="docs-main">
                <section id="getting-started" class="docs-section">
                    <h2>🚀 Getting Started</h2>
                    
                    <h3>Installation</h3>
                    <ol>
                        <li>Upload the <code>gaenity-community</code> folder to <code>/wp-content/plugins/</code></li>
                        <li>Activate the plugin through the Plugins menu in WordPress</li>
                        <li>Go to <strong>Gaenity Community → Settings</strong> to configure</li>
                    </ol>

                    <div class="info-box">
                        <strong>✨ First Time Setup:</strong> Use the "Add Dummy Content" button in Settings to create sample data for testing!
                    </div>

                    <h3>Requirements</h3>
                    <ul>
                        <li>WordPress 5.8 or higher</li>
                        <li>PHP 7.4 or higher</li>
                        <li>Elementor 3.0+ (optional - for widget support)</li>
                    </ul>
                </section>

                <section id="shortcodes" class="docs-section">
                    <h2>📝 All Shortcodes</h2>

                    <h3>Community Home</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_community_home]</code>
                    </div>
                    <p>Displays the main community landing page with stats, regions, industries, challenges, and recent discussions.</p>

                    <h3>Resources Grid</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_resources]</code>
                    </div>
                    <p>Shows all resources with download functionality and free/paid tabs.</p>

                    <h3>Registration Form</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_community_register]</code>
                    </div>
                    <p>Member registration form with profile fields.</p>

                    <h3>Login Form</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_community_login]</code>
                    </div>
                    <p>Simple login form for existing members.</p>

                    <h3>Discussion Form</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_discussion_form]</code>
                    </div>
                    <p>Form for members to submit new discussions (requires login).</p>

                    <h3>Discussion Board</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_discussion_board]</code>
                    </div>
                    <p>Lists all discussions with filtering by region, industry, and challenge.</p>

                    <h3>Polls</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_polls]</code>
                    </div>
                    <p>Community polls with live results (members only).</p>

                    <h3>Ask an Expert</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_expert_request]</code>
                    </div>
                    <p>Form to request expert consultation.</p>

                    <h3>Expert Registration</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_expert_register]</code>
                    </div>
                    <p>Form for experts to apply for approval.</p>

                    <h3>Contact Form</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_contact]</code>
                    </div>
                    <p>General contact form with marketing opt-in.</p>

                    <h3>Community Chat</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_community_chat]</code>
                    </div>
                    <p>Live community chat with auto-refresh.</p>

                    <h3>Member Dashboard</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_member_dashboard]</code>
                    </div>
                    <p>Personal dashboard showing member stats and activity (requires login).</p>

                    <h3>Expert Directory</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_expert_directory]</code>
                    </div>
                    <p>Directory of all approved experts with profiles.</p>
                </section>
                <h3>Community Home v2</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_community_home_v2]</code>
                    </div>
                    <p>Enhanced community home with navigation cards to Forum, Experts, Polls, Resources, Courses, and Community Guidelines.</p>

                    <h3>Polls Page</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_polls_page]</code>
                    </div>
                    <p>Standalone polls page with header and introduction.</p>

                    <h3>Checkout</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_checkout]</code>
                    </div>
                    <p>Payment checkout page for courses and paid resources.</p>
                <section id="payment-setup" class="docs-section">
                    <h2>💳 Payment Gateway Setup</h2>

                    <h3>Overview</h3>
                    <p>The plugin supports multiple payment gateways for selling courses, resources, and expert consultations.</p>

                    <h3>Supported Gateways</h3>
                    <ul>
                        <li><strong>Stripe</strong> - Credit/debit cards (Global)</li>
                        <li><strong>PayPal</strong> - PayPal accounts (Global)</li>
                        <li><strong>Paystack</strong> - Cards, bank transfer, mobile money (Africa)</li>
                        <li><strong>Bank Transfer</strong> - Manual payment verification</li>
                    </ul>

                    <h3>Configuration Steps</h3>
                    <ol>
                        <li>Go to <strong>Gaenity Community → Settings</strong></li>
                        <li>Scroll to <strong>Payment Gateways</strong> section</li>
                        <li>Check the gateways you want to enable</li>
                        <li>Select your currency (USD, EUR, GBP, NGN, etc.)</li>
                        <li>Enter API keys for each enabled gateway</li>
                        <li>Click <strong>Save Settings</strong></li>
                    </ol>

                    <div class="info-box">
                        <strong>💡 Testing:</strong> Always use test/sandbox mode keys during development. Switch to live keys only when ready for production.
                    </div>

                    <h3>Stripe Setup</h3>
                    <ol>
                        <li>Create account at <a href="https://stripe.com" target="_blank">stripe.com</a></li>
                        <li>Go to Developers → API Keys</li>
                        <li>Copy <strong>Publishable key</strong> and <strong>Secret key</strong></li>
                        <li>Paste into plugin settings</li>
                        <li>Select <strong>Test Mode</strong> for testing, <strong>Live Mode</strong> for production</li>
                    </ol>

                    <h3>PayPal Setup</h3>
                    <ol>
                        <li>Create account at <a href="https://developer.paypal.com" target="_blank">developer.paypal.com</a></li>
                        <li>Go to My Apps & Credentials</li>
                        <li>Create app and get <strong>Client ID</strong> and <strong>Secret</strong></li>
                        <li>Paste into plugin settings</li>
                        <li>Select <strong>Sandbox</strong> for testing, <strong>Live</strong> for production</li>
                    </ol>

                    <h3>Paystack Setup</h3>
                    <ol>
                        <li>Create account at <a href="https://paystack.com" target="_blank">paystack.com</a></li>
                        <li>Go to Settings → API Keys & Webhooks</li>
                        <li>Copy <strong>Public key</strong> and <strong>Secret key</strong></li>
                        <li>Paste into plugin settings</li>
                    </ol>

                    <h3>Bank Transfer Setup</h3>
                    <ol>
                        <li>Enable Bank Transfer in settings</li>
                        <li>Enter your bank account details in the text area</li>
                        <li>Include: Bank name, account number, account name, routing/sort code</li>
                        <li>These details will be shown to customers at checkout</li>
                    </ol>
                </section>

                <section id="courses-setup" class="docs-section">
                    <h2>📚 Enablement Courses</h2>

                    <h3>Creating Courses</h3>
                    <ol>
                        <li>Go to <strong>Enablement Courses → Add New</strong></li>
                        <li>Enter course title and description</li>
                        <li>Add featured image (recommended: 800x600px)</li>
                        <li>In the right sidebar, set:
                            <ul>
                                <li><strong>Course Type:</strong> Free, One-time Purchase, or Subscription</li>
                                <li><strong>Price:</strong> Amount in your selected currency</li>
                                <li><strong>Duration:</strong> e.g., "6 weeks", "3 months"</li>
                            </ul>
                        </li>
                        <li>Click <strong>Publish</strong></li>
                    </ol>

                    <h3>Displaying Courses</h3>
                    <div class="shortcode-box">
                        <button class="copy-btn" onclick="copyCode(this)">Copy</button>
                        <code>[gaenity_courses]</code>
                    </div>
                    <p>Shows all published courses in a beautiful grid with pricing and enrollment buttons.</p>

                    <h3>Checkout Process</h3>
                    <ol>
                        <li>Create a page titled "Checkout"</li>
                        <li>Add shortcode: <code>[gaenity_checkout]</code></li>
                        <li>Publish the page</li>
                        <li>When users click "Enroll Now" on any course, they'll be taken to checkout</li>
                    </ol>

                    <div class="warning-box">
                        <strong>⚠️ Important:</strong> Make sure at least one payment gateway is configured before selling courses!
                    </div>
                </section>

                <section id="transactions" class="docs-section">
                    <h2>💰 Managing Transactions</h2>

                    <h3>Viewing Transactions</h3>
                    <p>Go to <strong>Gaenity Community → Transactions</strong> to see all payments.</p>

                    <h3>Transaction Statuses</h3>
                    <ul>
                        <li><strong>Pending:</strong> Payment initiated but not completed</li>
                        <li><strong>Awaiting Confirmation:</strong> Bank transfer submitted, needs manual approval</li>
                        <li><strong>Completed:</strong> Payment successful and verified</li>
                        <li><strong>Failed:</strong> Payment did not go through</li>
                    </ul>

                    <h3>Approving Bank Transfers</h3>
                    <ol>
                        <li>Customer selects bank transfer at checkout</li>
                        <li>They see your bank details and make the transfer</li>
                        <li>Transaction appears as "Awaiting Confirmation"</li>
                        <li>Verify payment in your bank account</li>
                        <li>Click <strong>Approve</strong> button to mark as completed</li>
                        <li>Customer gets access to the course/resource</li>
                    </ol>
                </section>

                <section id="pages" class="docs-section">
                    <h2>📄 Recommended Page Setup</h2>

                    <table>
                        <thead>
                            <tr>
                                <th>Page Name</th>
                                <th>Shortcode</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Community Home</strong></td>
                                <td><code>[gaenity_community_home]</code></td>
                                <td>Main landing page</td>
                            </tr>
                            <tr>
                                <td><strong>Register</strong></td>
                                <td><code>[gaenity_community_register]</code></td>
                                <td>New member signup</td>
                            </tr>
                            <tr>
                                <td><strong>Login</strong></td>
                                <td><code>[gaenity_community_login]</code></td>
                                <td>Member login</td>
                            </tr>
                            <tr>
                                <td><strong>Dashboard</strong></td>
                                <td><code>[gaenity_member_dashboard]</code></td>
                                <td>Personal member area</td>
                            </tr>
                            <tr>
                                <td><strong>Resources</strong></td>
                                <td><code>[gaenity_resources]</code></td>
                                <td>Resource library</td>
                            </tr>
                            <tr>
                                <td><strong>Discussions</strong></td>
                                <td><code>[gaenity_discussion_board]</code></td>
                                <td>All discussions</td>
                            </tr>
                            <tr>
                                <td><strong>Ask an Expert</strong></td>
                                <td><code>[gaenity_expert_request]</code></td>
                                <td>Expert consultation</td>
                            </tr>
                            <tr>
                                <td><strong>Become an Expert</strong></td>
                                <td><code>[gaenity_expert_register]</code></td>
                                <td>Expert application</td>
                            </tr>
                            <tr>
                                <td><strong>Meet Our Experts</strong></td>
                                <td><code>[gaenity_expert_directory]</code></td>
                                <td>Expert profiles</td>
                            </tr>
                            <tr>
                                <td><strong>Contact</strong></td>
                                <td><code>[gaenity_contact]</code></td>
                                <td>Contact form</td>
                            </tr>
                            <tr>
                                <td><strong>Courses</strong></td>
                                <td><code>[gaenity_courses]</code></td>
                                <td>Enablement courses catalog</td>
                            </tr>
                            <tr>
                                <td><strong>Checkout</strong></td>
                                <td><code>[gaenity_checkout]</code></td>
                                <td>Payment processing page</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="success-box">
                        <strong>💡 Pro Tip:</strong> Create all these pages, then set their URLs in <strong>Gaenity Community → Settings → Page URLs</strong> so navigation buttons work correctly!
                    </div>
                </section>
                <section id="forum-setup" class="docs-section">
                    <h2>💬 Forum Setup</h2>

                    <h3>Overview</h3>
                    <p>The forum is automatically available at <code>/community-discussions/</code> once you activate the plugin.</p>

                    <h3>Key Features</h3>
                    <ul>
                        <li><strong>Filtering:</strong> Users can filter by Industry and Region</li>
                        <li><strong>Voting:</strong> Upvote/downvote discussions</li>
                        <li><strong>Comments:</strong> Reply to discussions</li>
                        <li><strong>Sidebar Widgets:</strong> Quick Actions, Ask Expert, Become Expert, Polls</li>
                    </ul>

                    <h3>URL Structure</h3>
                    <ul>
                        <li><strong>Forum Archive:</strong> <code>/community-discussions/</code></li>
                        <li><strong>Single Discussion:</strong> <code>/community-discussions/discussion-title/</code></li>
                        <li><strong>Filtered:</strong> <code>/community-discussions/?industry=retail&region=africa</code></li>
                    </ul>

                    <h3>Creating a Discussion Form Page</h3>
                    <ol>
                        <li>Create page: "Start Discussion"</li>
                        <li>Add shortcode: <code>[gaenity_discussion_form]</code></li>
                        <li>Go to Settings → Page URLs</li>
                        <li>Set "Discussion Form Page" URL</li>
                        <li>Save settings</li>
                    </ol>

                    <div class="info-box">
                        <strong>💡 Analytics:</strong> All discussion submissions capture Region, Industry, and Role for reporting purposes.
                    </div>
                </section>

                <section id="complete-setup" class="docs-section">
                    <h2>🚀 Complete Site Setup Guide</h2>

                    <h3>Step 1: Create All Pages</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Page Title</th>
                                <th>Shortcode</th>
                                <th>Purpose</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Community Home</strong></td>
                                <td><code>[gaenity_community_home_v2]</code></td>
                                <td>Main landing with navigation</td>
                            </tr>
                            <tr>
                                <td><strong>Register</strong></td>
                                <td><code>[gaenity_community_register]</code></td>
                                <td>New member signup</td>
                            </tr>
                            <tr>
                                <td><strong>Login</strong></td>
                                <td><code>[gaenity_community_login]</code></td>
                                <td>Member login</td>
                            </tr>
                            <tr>
                                <td><strong>Dashboard</strong></td>
                                <td><code>[gaenity_member_dashboard]</code></td>
                                <td>Personal member area</td>
                            </tr>
                            <tr>
                                <td><strong>Start Discussion</strong></td>
                                <td><code>[gaenity_discussion_form]</code></td>
                                <td>Post new discussion</td>
                            </tr>
                            <tr>
                                <td><strong>Resources</strong></td>
                                <td><code>[gaenity_resources]</code></td>
                                <td>Resource library</td>
                            </tr>
                            <tr>
                                <td><strong>Experts</strong></td>
                                <td><code>[gaenity_expert_directory]</code></td>
                                <td>Expert profiles</td>
                            </tr>
                            <tr>
                                <td><strong>Ask an Expert</strong></td>
                                <td><code>[gaenity_expert_request]</code></td>
                                <td>Request consultation</td>
                            </tr>
                            <tr>
                                <td><strong>Become an Expert</strong></td>
                                <td><code>[gaenity_expert_register]</code></td>
                                <td>Expert application</td>
                            </tr>
                            <tr>
                                <td><strong>Polls</strong></td>
                                <td><code>[gaenity_polls_page]</code></td>
                                <td>Community polls</td>
                            </tr>
                            <tr>
                                <td><strong>Courses</strong></td>
                                <td><code>[gaenity_courses]</code></td>
                                <td>Enablement courses</td>
                            </tr>
                            <tr>
                                <td><strong>Checkout</strong></td>
                                <td><code>[gaenity_checkout]</code></td>
                                <td>Payment processing</td>
                            </tr>
                            <tr>
                                <td><strong>Contact</strong></td>
                                <td><code>[gaenity_contact]</code></td>
                                <td>Contact form</td>
                            </tr>
                        </tbody>
                    </table>

                    <h3>Step 2: Configure Settings</h3>
                    <p>Go to <strong>Gaenity Community → Settings</strong> and configure:</p>
                    <ol>
                        <li><strong>Colors:</strong> Set primary and secondary brand colors</li>
                        <li><strong>Page URLs:</strong> Enter URL for each page you created</li>
                        <li><strong>Payment Gateways:</strong> Enable and configure payment methods</li>
                        <li><strong>Currency:</strong> Select your preferred currency</li>
                    </ol>

                    <h3>Step 3: Add Demo Content (Optional)</h3>
                    <ol>
                        <li>Go to <strong>Gaenity Community → Settings</strong></li>
                        <li>Scroll to "Demo Content" section</li>
                        <li>Click "Add Dummy Content"</li>
                        <li>This creates sample discussions, resources, and polls for testing</li>
                    </ol>

                    <h3>Step 4: Create Initial Content</h3>
                    <ul>
                        <li><strong>Resources:</strong> Gaenity Community → Resources → Add New</li>
                        <li><strong>Courses:</strong> Enablement Courses → Add New</li>
                        <li><strong>Polls:</strong> Community Polls → Add New</li>
                    </ul>

                    <div class="success-box">
                        <strong>✅ You're Done!</strong> Your community is now fully functional with Forum, Resources, Experts, Polls, Courses, and Payment processing!
                    </div>
                </section>

                <section id="analytics" class="docs-section">
                    <h2>📊 Analytics & Reporting</h2>

                    <h3>Data Collected</h3>
                    <p>All user interactions capture Region, Industry, and Role for analytics:</p>
                    <ul>
                        <li><strong>User Registrations:</strong> Full profile with demographics</li>
                        <li><strong>Discussions:</strong> Region, Industry, Challenge tagged</li>
                        <li><strong>Resource Downloads:</strong> User Region, Industry, Role</li>
                        <li><strong>Poll Votes:</strong> Region and Industry segmentation</li>
                        <li><strong>Expert Requests:</strong> Region, Industry, Challenge</li>
                        <li><strong>Transactions:</strong> Purchase history by user/item</li>
                    </ul>

                    <h3>Viewing Reports</h3>
                    <p>Access data from admin pages:</p>
                    <ul>
                        <li><strong>Resource Downloads:</strong> See who downloaded what, with demographics</li>
                        <li><strong>Transactions:</strong> View all payments and statuses</li>
                        <li><strong>Expert Requests:</strong> Filter by region/industry/challenge</li>
                        <li><strong>Contact Messages:</strong> View all inquiries</li>
                        <li><strong>Chat Messages:</strong> Monitor community conversations</li>
                    </ul>

                    <h3>Exporting Data</h3>
                    <p>Use these SQL queries in phpMyAdmin or similar tool:</p>
                    <div class="shortcode-box">
                        <code>SELECT * FROM wp_gaenity_resource_downloads WHERE region = 'Africa'</code>
                    </div>
                    <div class="shortcode-box">
                        <code>SELECT * FROM wp_gaenity_transactions WHERE status = 'completed'</code>
                    </div>
                </section>

                <section id="settings" class="docs-section">
                    <h2>⚙️ Plugin Settings</h2>

                    <h3>Color Customization</h3>
                    <p>Go to <strong>Gaenity Community → Settings</strong></p>
                    <ul>
                        <li><strong>Primary Color:</strong> Main brand color for buttons and highlights</li>
                        <li><strong>Secondary Color:</strong> Accent color for gradients</li>
                    </ul>

                    <h3>Page URLs Configuration</h3>
                    <p>Set custom URLs for navigation buttons:</p>
                    <ul>
                        <li><strong>Registration Page:</strong> Where "Create Account" button links to</li>
                        <li><strong>Ask Expert Page:</strong> Where "Ask an Expert" button links to</li>
                        <li><strong>Become Expert Page:</strong> Where "Become an Expert" button links to</li>
                        <li><strong>Resources Page:</strong> Where "Browse Resources" links to</li>
                    </ul>

                    <div class="warning-box">
                        <strong>⚠️ Important:</strong> If you leave URLs empty, buttons will use anchor links (#) which only work if all content is on the same page.
                    </div>
                </section>

                <section id="admin" class="docs-section">
                    <h2>🔧 Admin Features</h2>

                    <div class="feature-grid">
                        <div class="feature-card">
                            <h4>📊 Dashboard</h4>
                            <p>View community stats and quick actions</p>
                        </div>
                        <div class="feature-card">
                            <h4>👨‍🏫 Expert Requests</h4>
                            <p>Approve/reject expert applications and manage help requests</p>
                        </div>
                        <div class="feature-card">
                            <h4>📥 Resource Downloads</h4>
                            <p>Track who downloaded which resources</p>
                        </div>
                        <div class="feature-card">
                            <h4>✉️ Contact Messages</h4>
                            <p>View and manage contact form submissions</p>
                        </div>
                        <div class="feature-card">
                            <h4>💬 Chat Messages</h4>
                            <p>Moderate community chat</p>
                        </div>
                        <div class="feature-card">
                            <h4>📝 Discussions</h4>
                            <p>Manage community discussions</p>
                        </div>
                    </div>

                    <h3>Approving Experts</h3>
                    <ol>
                        <li>Go to <strong>Gaenity Community → Expert Requests</strong></li>
                        <li>Filter by "Expert Registrations"</li>
                        <li>Review the application</li>
                        <li>Click "Approve" to create their WordPress account with expert role</li>
                        <li>They will appear in the Expert Directory</li>
                    </ol>
                </section>

                <section id="workflows" class="docs-section">
                    <h2>🔄 User Workflows</h2>

                    <h3>Member Journey</h3>
                    <ol>
                        <li>Visitor arrives at Community Home</li>
                        <li>Clicks "Create Account" → Fills registration form</li>
                        <li>Becomes a member → Can access Dashboard</li>
                        <li>Posts discussions, downloads resources, requests expert help</li>
                        <li>Dashboard shows their activity stats</li>
                    </ol>

                    <h3>Expert Journey</h3>
                    <ol>
                        <li>Visitor clicks "Become an Expert"</li>
                        <li>Fills expert registration form with credentials</li>
                        <li>Admin reviews application in backend</li>
                        <li>Admin approves → Expert account created</li>
                        <li>Expert appears in Expert Directory</li>
                        <li>Members can request consultations</li>
                    </ol>
                </section>

                <section id="customization" class="docs-section">
                    <h2>🎨 Customization</h2>

                    <h3>Using with Elementor</h3>
                    <p>The plugin includes an Elementor widget:</p>
                    <ol>
                        <li>Edit page with Elementor</li>
                        <li>Search for "Gaenity Community Block" widget</li>
                        <li>Drag to canvas</li>
                        <li>Select which block to display from dropdown</li>
                    </ol>

                    <h3>Custom CSS</h3>
                    <p>All plugin elements use classes prefixed with <code>.gaenity-</code> for easy targeting:</p>
                    <div class="shortcode-box">
                        <code>.gaenity-button { /* your styles */ }</code>
                    </div>
                </section>

                <section id="troubleshooting" class="docs-section">
                    <h2>🔍 Troubleshooting</h2>

                    <h3>Buttons Not Working</h3>
                    <p><strong>Problem:</strong> Navigation buttons link to # and don't go anywhere</p>
                    <p><strong>Solution:</strong> Set page URLs in <strong>Gaenity Community → Settings → Page URLs</strong></p>

                    <h3>No Experts Showing</h3>
                    <p><strong>Problem:</strong> Expert Directory is empty</p>
                    <p><strong>Solution:</strong> Go to <strong>Gaenity Community → Expert Requests</strong> and approve expert applications</p>

                    <h3>Forms Not Submitting</h3>
                    <p><strong>Problem:</strong> Forms show "Security check failed"</p>
                    <p><strong>Solution:</strong> Clear your cache and refresh the page. Check if you have caching plugins that might interfere with nonces.</p>

                    <h3>Discussions Not Filtering</h3>
                    <p><strong>Problem:</strong> Clicking region/industry doesn't filter</p>
                    <p><strong>Solution:</strong> Ensure the archive template is in <code>gaenity-community/templates/</code> folder</p>
                </section>
            </main>
        </div>
    </div>

    <script>
        function copyCode(btn) {
            const code = btn.nextElementSibling.textContent;
            navigator.clipboard.writeText(code).then(() => {
                btn.textContent = 'Copied!';
                setTimeout(() => {
                    btn.textContent = 'Copy';
                }, 2000);
            });
        }

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>