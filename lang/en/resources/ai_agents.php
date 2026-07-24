<?php

declare(strict_types=1);

return [
    // Empty state
    'empty_heading' => 'No AI agents yet',
    'empty_desc' => 'Create an AI agent to draft replies and power your auto-reply automations in your brand voice.',
    'empty_cta' => 'New AI agent',

    // Table
    'col_name' => 'Name',
    'col_tone' => 'Tone',
    'name' => 'Name',
    'col_native_lang' => 'Native lang',
    'col_default' => 'Default',
    'col_updated' => 'Updated',
    'test_preview' => 'Test & preview',
    'test_heading' => 'Test reply',
    'close' => 'Close',
    'no_reviews_to_test' => 'No reviews to test on yet, sync some reviews first.',
    'generation_failed' => 'Generation failed: :error',
    'set_default' => 'Set default',

    // Form
    'section' => 'Your AI agent',
    'section_desc' => 'Give the agent a name and describe how it should reply. Used by auto-reply automations and the "draft with AI" button.',
    'describe' => 'Describe your agent',
    'describe_helper' => 'The full instructions / persona, how to classify the review and how to respond, tone & style, personalization rules, etc.',
    'tone' => 'Tone of voice',
    'reply_native' => 'Reply in the review\'s language',
    'reply_native_helper' => 'The agent responds in the same language as the review.',
    'default_agent' => 'Default agent',
    'default_agent_helper' => 'Used when an automation doesn\'t specify an agent.',

    // Knowledge base
    'knowledge' => 'Knowledge base (optional)',
    'knowledge_helper' => 'Business facts the agent can use in replies: opening hours, policies, room/service names, offers, FAQs. Kept factual, never invented beyond this.',
    'knowledge_ph' => 'e.g. Open Mon–Sun 10:00–22:00. Rooms: The Heist, Prison Break, Haunted Manor. Groups of 2–6. Booking at example.com or +43 ...',

    // Test panel
    'test_section' => 'Test on a review',
    'test_section_desc' => 'Pick a real review and generate a draft with the current (unsaved) settings, then tweak.',
    'test_pick_review' => 'Review',
    'test_pick_placeholder' => 'Choose a synced review…',
    'test_review_text' => 'Review',
    'test_generate' => 'Generate draft',
    'test_result' => 'Generated draft',
    'test_need_review' => 'Pick a review to test on first.',

    // AI description generator
    'generate_label' => 'Generate with AI',
    'generate_heading' => 'Generate the description with AI',
    'generate_desc' => 'Add your website and/or a few words about the business, and AI will draft the agent instructions for you. You can edit the result afterwards.',
    'generate_submit' => 'Generate',
    'generate_url' => 'Website URL',
    'generate_notes' => 'Anything to add (optional)',
    'generate_notes_ph' => 'e.g. family-run Italian restaurant, focus on friendly service, mention our terrace in summer',
    'generate_need_input' => 'Add a website URL or a short description first.',
    'generate_rate_limited' => 'Too many generations. Please wait a bit and try again.',
    'generate_done' => 'Description generated, review and tweak it as needed.',
    'generate_failed' => 'Could not generate the description. Please try again or write it manually.',

    // Shared reply rules (workspace-wide, applied to every agent)
    'shared_rules' => 'Shared rules',
    'shared_rules_heading' => 'Shared reply rules',
    'shared_rules_desc' => 'These rules apply on top of every agent, in every AI reply. Perfect for style corrections you never want to repeat per agent.',
    'shared_rules_placeholder' => "e.g.\nIn German replies say \"Raum\" or \"Escape Room\", never \"Room\" as a German noun.\nNever promise discounts or refunds.\nSign replies without a name.",
    'shared_rules_save' => 'Save rules',
    'shared_rules_saved' => 'Shared rules saved',
];
