<?php

namespace Tests\Feature;

use Tests\TestCase;

class ChatRobotComponentTest extends TestCase
{
    public function test_the_robot_renders_without_a_logo(): void
    {
        $this->blade(
            '<x-chat-robot primaryColor="#112233" secondaryColor="#445566" avatarType="robot" welcomeMessage="مرحبا" />'
        )
            ->assertSee('id="chatBtn"', false)
            ->assertSee('data-type="robot"', false)
            ->assertSee('id="robotSvg"', false)
            ->assertSee('viewBox="0 0 64 72"', false)
            ->assertSee('stop-color="#112233"', false)
            ->assertSee('fill="#112233"', false)
            ->assertSee('--rc: #112233', false)
            ->assertSee('--rc2: #445566', false)
            ->assertDontSee('<img', false);
    }

    public function test_the_logo_variant_uses_the_logo_image_instead_of_the_robot(): void
    {
        $this->blade(
            '<x-chat-robot avatarType="logo" logoUrl="/storage/logos/brand.png" />'
        )
            ->assertSee('data-type="logo"', false)
            ->assertSee('class="robot-logo"', false)
            ->assertSee('<img src="/storage/logos/brand.png"', false)
            ->assertDontSee('id="robotSvg"', false);
    }

    public function test_the_logo_variant_falls_back_to_the_robot_without_a_logo(): void
    {
        $this->blade('<x-chat-robot avatarType="logo" />')
            ->assertSee('data-type="robot"', false)
            ->assertSee('id="robotSvg"', false)
            ->assertDontSee('class="robot-logo"', false);
    }

    public function test_the_chef_and_star_variants_are_rendered(): void
    {
        $this->blade('<x-chat-robot avatarType="chef" />')
            ->assertSee('data-type="chef"', false)
            ->assertSee('id="robotSvg"', false);

        $this->blade('<x-chat-robot avatarType="star" />')
            ->assertSee('data-type="star"', false)
            ->assertSee('id="robotSvg"', false);
    }

    public function test_the_drag_position_is_stored_in_local_storage(): void
    {
        $this->blade('<x-chat-robot />')
            ->assertSee('localStorage.setItem(positionKey', false)
            ->assertSee('localStorage.getItem(positionKey', false)
            ->assertSee("const positionKey = 'chatBtnPos'", false)
            ->assertSee('btn.style.left', false)
            ->assertSee('btn.style.bottom', false);
    }
}
